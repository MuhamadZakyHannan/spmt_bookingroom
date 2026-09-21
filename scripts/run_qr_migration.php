<?php

require_once __DIR__ . '/../config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$direction = $argv[1] ?? 'up';
if (!in_array($direction, ['up', 'down'], true)) {
    fwrite(STDERR, "Pemakaian: php scripts/run_qr_migration.php [up|down]\n");
    exit(1);
}

if (!$pdo) {
    fwrite(STDERR, "Koneksi database tidak tersedia. Periksa konfigurasi .env/XAMPP.\n");
    exit(2);
}

$tableCheck = $pdo->query("SHOW TABLES LIKE 'booking_checkin_tokens'");
$isInstalled = (bool)$tableCheck->fetch();

if ($direction === 'up' && $isInstalled) {
    echo "Migrasi QR check-in sudah terpasang.\n";
    exit(0);
}

if ($direction === 'down' && !$isInstalled) {
    echo "Migrasi QR check-in belum terpasang; tidak ada yang di-rollback.\n";
    exit(0);
}

$file = __DIR__ . '/../migrations/20260921_qr_checkin_phase_two_' . $direction . '.sql';

try {
    $pdo->exec(file_get_contents($file));
    echo $direction === 'up'
        ? "Migrasi QR check-in berhasil dipasang.\n"
        : "Migrasi QR check-in berhasil di-rollback.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migrasi QR gagal: " . $e->getMessage() . "\n");
    exit(3);
}
