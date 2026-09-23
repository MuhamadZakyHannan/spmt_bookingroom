<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/core/Organization.php';
require_once __DIR__ . '/../app/core/PasswordPolicy.php';
require_once __DIR__ . '/../app/core/UsernamePolicy.php';
require_once __DIR__ . '/../app/models/UserModel.php';

function expectAccount(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException('[FAIL] ' . $message);
    }

    echo '[PASS] ' . $message . PHP_EOL;
}

expectAccount(PasswordPolicy::validationError('Aa1#abc') !== null, 'Password tujuh karakter ditolak oleh kebijakan keamanan.');
expectAccount(PasswordPolicy::validationError('Aa1#abcd') === null, 'Password delapan karakter dengan seluruh kriteria diterima.');
expectAccount(PasswordPolicy::validationError('Password123') === null, 'Password delapan karakter tanpa simbol diterima.');
expectAccount(UsernamePolicy::normalize('  Fikus.Admin  ') === 'fikus.admin', 'Username dinormalisasi secara konsisten.');
expectAccount(UsernamePolicy::validationError('fikus.admin') === null, 'Username dengan format yang benar diterima.');
expectAccount(UsernamePolicy::validationError('nama pengguna') !== null, 'Username yang mengandung spasi ditolak.');
expectAccount(UsernamePolicy::validationError('fikus@company.com') !== null, 'Alamat email tidak lagi digunakan sebagai format username baru.');
expectAccount(Organization::isValidDepartment('SPMT - Teknik & IT'), 'Divisi resmi dikenali oleh sumber data organisasi.');
expectAccount(! Organization::isValidDepartment('Divisi Tidak Resmi'), 'Divisi di luar daftar resmi ditolak.');

$pdo = Database::getInstance()->getConnection();
if (! $pdo) {
    throw new RuntimeException('Koneksi database tidak tersedia.');
}

$users = $pdo->query("SELECT * FROM users WHERE role != 'super_admin' ORDER BY id LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
if (count($users) < 2) {
    throw new RuntimeException('Minimal dua akun non-Super Admin diperlukan untuk pengujian.');
}

$target = $users[0];
$other = $users[1];
$model = new UserModel();
expectAccount($model->usernameExistsForOtherUser($other['username'], (int)$target['id']), 'Username milik akun lain terdeteksi sebagai duplikat.');
expectAccount(!$model->usernameExistsForOtherUser($target['username'], (int)$target['id']), 'Username akun sendiri tidak dianggap duplikat saat diedit.');

$pdo->beginTransaction();
try {
    $newName = 'TEST EDIT ACCOUNT';
    $newDepartment = 'SPMT - Teknik & IT';
    $newPassword = 'Aman#Edit99';
    $updated = $model->updateAccount((int)$target['id'], [
        'name' => $newName,
        'username' => $target['username'],
        'department' => $newDepartment,
        'role' => $target['role'],
        'password' => $newPassword,
    ]);
    expectAccount($updated, 'Model berhasil memperbarui data akun.');

    $afterUpdate = $model->getById((int)$target['id']);
    expectAccount(
        $afterUpdate['name'] === $newName && $afterUpdate['department'] === $newDepartment,
        'Nama dan divisi akun tersimpan dengan benar.'
    );
    expectAccount(
        $afterUpdate['username'] === $target['username'] && $afterUpdate['email'] === $target['username'],
        'Kolom kompatibilitas lama tetap sinkron dengan username.'
    );
    expectAccount(password_verify($newPassword, $afterUpdate['password']), 'Password baru disimpan dalam bentuk hash yang valid.');
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

$restored = $model->getById((int)$target['id']);
expectAccount($restored['name'] === $target['name'], 'Transaksi pengujian mengembalikan akun seperti semula.');

$userViewSource = file_get_contents(__DIR__ . '/../app/views/admin/users.php');
$loginViewSource = file_get_contents(__DIR__ . '/../app/views/auth/login.php');
$registerViewSource = file_get_contents(__DIR__ . '/../app/views/auth/register.php');
expectAccount(strpos($userViewSource, 'editPasswordPopover') !== false, 'Form edit menyediakan popover persyaratan password.');
expectAccount(strpos($userViewSource, 'setCustomValidity') !== false, 'Password lemah dicegah pada validasi browser sebelum dikirim.');
expectAccount(
    str_contains($loginViewSource, 'name="username"')
        && str_contains($registerViewSource, 'name="username"')
        && !str_contains($loginViewSource . $registerViewSource, 'type="email"'),
    'Login dan pendaftaran menggunakan istilah serta input Username.'
);

echo PHP_EOL . 'Hasil: 18 lulus, 0 gagal.' . PHP_EOL;
