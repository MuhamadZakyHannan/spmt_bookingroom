<?php

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/UserModel.php';
require_once __DIR__ . '/../app/core/helpers.php';
require_once __DIR__ . '/../app/controllers/AdminUserController.php';

function assertTest(bool $condition, string $message): void
{
    if (!$condition) {
        echo "[FAIL] {$message}" . PHP_EOL;
        exit(1);
    }
    echo "[PASS] {$message}" . PHP_EOL;
}

$userModel = new UserModel();
$db = Database::getInstance()->getConnection();

echo "Memulai pengujian fungsionalitas hapus user (Admin & Super Admin)..." . PHP_EOL;

// 1. Verifikasi countByRole
$superAdminCount = $userModel->countByRole('super_admin');
assertTest($superAdminCount >= 1, "UserModel::countByRole('super_admin') mengembalikan jumlah valid ({$superAdminCount}).");

// 2. Buat user dummy dengan role admin dan super_admin untuk pengujian hapus
$db->beginTransaction();
try {
    // Dummy admin
    $adminCreated = $userModel->create([
        'name' => 'Test Admin Target',
        'username' => 'test_target_admin',
        'department' => 'SPMT - Teknik & IT',
        'role' => 'admin',
        'password' => 'AdminPass123'
    ]);
    assertTest($adminCreated, "Berhasil membuat user uji dengan role 'admin'.");
    $targetAdmin = $userModel->getByUsername('test_target_admin');
    assertTest($targetAdmin && $targetAdmin['role'] === 'admin', "Target user ber-role admin terverifikasi.");

    // Dummy super admin
    $superAdminCreated = $userModel->create([
        'name' => 'Test Super Admin Target',
        'username' => 'test_target_super_admin',
        'department' => 'SPMT - Teknik & IT',
        'role' => 'super_admin',
        'password' => 'SuperPass123'
    ]);
    assertTest($superAdminCreated, "Berhasil membuat user uji dengan role 'super_admin'.");
    $targetSuperAdmin = $userModel->getByUsername('test_target_super_admin');
    assertTest($targetSuperAdmin && $targetSuperAdmin['role'] === 'super_admin', "Target user ber-role super_admin terverifikasi.");

    // Test hapus user role 'admin'
    $deletedAdmin = $userModel->delete($targetAdmin['id']);
    assertTest($deletedAdmin, "UserModel::delete() berhasil menghapus user dengan role 'admin'.");
    assertTest($userModel->getById($targetAdmin['id']) === false, "User role 'admin' sudah tidak ada di database setelah dihapus.");

    // Test hapus user role 'super_admin' (karena ada lebih dari 1 super_admin)
    $newSuperAdminCount = $userModel->countByRole('super_admin');
    assertTest($newSuperAdminCount >= 2, "Jumlah super admin saat ini ({$newSuperAdminCount}) memungkinkan penghapusan.");
    $deletedSuperAdmin = $userModel->delete($targetSuperAdmin['id']);
    assertTest($deletedSuperAdmin, "UserModel::delete() berhasil menghapus user dengan role 'super_admin'.");
    assertTest($userModel->getById($targetSuperAdmin['id']) === false, "User role 'super_admin' sudah tidak ada di database setelah dihapus.");

} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
}

// 3. Verifikasi UI di users.php
$usersPhp = file_get_contents(__DIR__ . '/../app/views/admin/users.php');

assertTest(
    strpos($usersPhp, "u['role'] !== 'super_admin'") === false,
    "users.php tidak lagi memblokir tombol hapus untuk akun super_admin."
);

assertTest(
    strpos($usersPhp, "action=\"admin_users.php\"") !== false && strpos($usersPhp, "value=\"delete\"") !== false,
    "Formulir aksi delete terpasang pada tombol hapus."
);

assertTest(
    strpos($usersPhp, "currentSessionIsAdmin") !== false,
    "users.php mendefinisikan flag currentSessionIsAdmin untuk akses admin."
);

assertTest(
    strpos($usersPhp, "if (currentSessionIsAdmin && !isSelf)") !== false,
    "renderUsersTable di JavaScript mengizinkan role admin dan super_admin menghapus akun non-self."
);

// 4. Verifikasi controller AdminUserController.php
$controllerPhp = file_get_contents(__DIR__ . '/../app/controllers/AdminUserController.php');

assertTest(
    strpos($controllerPhp, "Tidak dapat menghapus Super Admin terakhir di sistem.") !== false,
    "AdminUserController memiliki perlindungan agar Super Admin terakhir tidak terhapus."
);

assertTest(
    strpos($controllerPhp, "!is_admin()") !== false,
    "AdminUserController memeriksa hak akses is_admin() untuk eksekusi hapus."
);

echo PHP_EOL . "Seluruh pengujian fungsionalitas hapus user berhasil 100%!" . PHP_EOL;
