<?php

require_once __DIR__ . '/../config.php';

function expectRole(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException('[FAIL] ' . $message);
    }

    echo '[PASS] ' . $message . PHP_EOL;
}

$originalSession = $_SESSION ?? [];

try {
    $_SESSION['role'] = 'user';
    expectRole(! is_admin() && ! is_super_admin(), 'Role user tidak memperoleh akses administrator.');

    $_SESSION['role'] = 'admin';
    expectRole(is_admin() && ! is_super_admin(), 'Role admin tetap memperoleh akses administrator biasa.');

    $_SESSION['role'] = 'super_admin';
    expectRole(is_admin(), 'Super Admin mewarisi seluruh akses administrator.');
    expectRole(is_super_admin(), 'Helper mengenali role Super Admin secara khusus.');
} finally {
    $_SESSION = $originalSession;
}

$pdo = Database::getInstance()->getConnection();
if (! $pdo) {
    throw new RuntimeException('Koneksi database tidak tersedia.');
}

$statement = $pdo->prepare('SELECT role, department FROM users WHERE email = ?');
$statement->execute(['fikus@company.com']);
$fikus = $statement->fetch(PDO::FETCH_ASSOC);

expectRole(($fikus['role'] ?? '') === 'super_admin', 'Akun fikus tersimpan sebagai Super Admin.');
expectRole(($fikus['department'] ?? '') === 'SPMT - Teknik & IT', 'Akun fikus terhubung ke divisi SPMT Teknik & IT.');

$notificationSource = file_get_contents(__DIR__ . '/../app/models/NotificationModel.php');
expectRole(strpos($notificationSource, "IN ('admin', 'super_admin')") !== false, 'Super Admin ikut menerima notifikasi administrator.');

$userAdminSource = file_get_contents(__DIR__ . '/../app/controllers/AdminController.php');
expectRole(strpos($userAdminSource, 'Hanya Super Admin yang dapat mengubah role pengguna.') !== false, 'Perubahan role dibatasi untuk Super Admin.');
expectRole(strpos($userAdminSource, 'Hanya Super Admin yang dapat menghapus akun pengguna.') !== false, 'Penghapusan akun dibatasi untuk Super Admin.');

echo PHP_EOL . 'Hasil: 9 lulus, 0 gagal.' . PHP_EOL;
