<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/core/Organization.php';
require_once __DIR__ . '/../app/core/PasswordPolicy.php';
require_once __DIR__ . '/../app/core/UsernamePolicy.php';
require_once __DIR__ . '/../app/models/UserModel.php';

function assertSync(bool $cond, string $msg): void
{
    if (!$cond) {
        throw new RuntimeException('[FAIL] ' . $msg);
    }
    echo '[PASS] ' . $msg . PHP_EOL;
}

$pdo = Database::getInstance()->getConnection();
if (!$pdo) {
    throw new RuntimeException('Database tidak terhubung.');
}

$model = new UserModel();

// Test 1: Fallback saat name kosong pada model create()
$testUsername1 = 'synctest' . time();
$pdo->beginTransaction();
try {
    $created = $model->create([
        'name' => '',
        'username' => $testUsername1,
        'password' => 'Password123',
        'department' => 'SPMT - Operasional',
        'role' => 'user'
    ]);
    assertSync($created, 'UserModel::create() berhasil membuat akun tanpa nama eksplisit.');

    $user = $model->getByUsername($testUsername1);
    assertSync($user !== false, 'Akun uji ditemukan dalam database.');
    assertSync($user['name'] === $testUsername1, 'Nama lengkap otomatis menyesuaikan dengan username saat kosong.');
    assertSync($user['username'] === $testUsername1, 'Username tersimpan dengan benar.');
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

// Test 2: Ketika name diisi eksplisit seperti unit kerja
$testUsername2 = 'spmtunit' . time();
$testName2 = 'SPMT Unit Operasi Khusus';
$pdo->beginTransaction();
try {
    $created = $model->create([
        'name' => $testName2,
        'username' => $testUsername2,
        'password' => 'Password123',
        'department' => 'SPMT - Pendukung Operasi',
        'role' => 'user'
    ]);
    assertSync($created, 'UserModel::create() berhasil membuat akun dengan nama unit spesifik.');

    $user = $model->getByUsername($testUsername2);
    assertSync($user['name'] === $testName2, 'Nama spesifik unit kerja dipertahankan.');
    assertSync($user['username'] === $testUsername2, 'Username unit kerja tersimpan dengan benar.');
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

// Test 3: Ketika divisi baru yang belum ada ditambahkan
$testUsername3 = 'divisibaru' . time();
$newDepartmentName = 'Divisi Inovasi Maritim Baru';
$pdo->beginTransaction();
try {
    $created = $model->create([
        'username' => $testUsername3,
        'password' => 'Password123',
        'department' => $newDepartmentName,
        'role' => 'user'
    ]);
    assertSync($created, 'UserModel::create() berhasil membuat akun dengan divisi baru yang belum ada.');

    $user = $model->getByUsername($testUsername3);
    assertSync($user !== false, 'Akun dengan divisi baru berhasil ditemukan.');
    assertSync($user['department'] === $newDepartmentName, 'Divisi baru tersimpan dengan tepat pada database.');
    assertSync($user['name'] === $testUsername3, 'Nama otomatis menggunakan username saat tidak ada input nama.');
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

// Test 4: Verifikasi komponen UI di users.php
$usersPhp = file_get_contents(__DIR__ . '/../app/views/admin/users.php');
assertSync(strpos($usersPhp, 'id="addUserName"') === false, 'Kolom input nama lengkap telah dihapus dari addUserModal.');
assertSync(strpos($usersPhp, 'id="addUserDepartment"') !== false, 'Input divisi tersedia.');
assertSync(strpos($usersPhp, 'list="departmentsDatalist"') === false, 'Input divisi murni ketik manual tanpa dropdown/datalist.');
assertSync(strpos($usersPhp, 'placeholder="Contoh: SPMT - Kreatif"') !== false, 'Placeholder isian format divisi diterapkan.');
assertSync(strpos($usersPhp, 'Budi Santoso') === false, 'Placeholder lama "Budi Santoso" telah dihapus sepenuhnya.');

echo PHP_EOL . 'Seluruh pengujian penyesuaian modal tambah user berhasil 100%!' . PHP_EOL;
