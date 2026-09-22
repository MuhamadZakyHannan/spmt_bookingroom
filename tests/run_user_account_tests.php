<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/core/Organization.php';
require_once __DIR__ . '/../app/core/PasswordPolicy.php';
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
expectAccount($model->emailExistsForOtherUser($other['email'], (int)$target['id']), 'Email milik akun lain terdeteksi sebagai duplikat.');
expectAccount(!$model->emailExistsForOtherUser($target['email'], (int)$target['id']), 'Email akun sendiri tidak dianggap duplikat saat diedit.');

$pdo->beginTransaction();
try {
    $newName = 'TEST EDIT ACCOUNT';
    $newDepartment = 'SPMT - Teknik & IT';
    $newPassword = 'Aman#Edit99';
    $updated = $model->updateAccount((int)$target['id'], [
        'name' => $newName,
        'email' => $target['email'],
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
    expectAccount(password_verify($newPassword, $afterUpdate['password']), 'Password baru disimpan dalam bentuk hash yang valid.');
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

$restored = $model->getById((int)$target['id']);
expectAccount($restored['name'] === $target['name'], 'Transaksi pengujian mengembalikan akun seperti semula.');

$userViewSource = file_get_contents(__DIR__ . '/../app/views/admin/users.php');
expectAccount(strpos($userViewSource, 'editPasswordPopover') !== false, 'Form edit menyediakan popover persyaratan password.');
expectAccount(strpos($userViewSource, 'setCustomValidity') !== false, 'Password lemah dicegah pada validasi browser sebelum dikirim.');

echo PHP_EOL . 'Hasil: 12 lulus, 0 gagal.' . PHP_EOL;
