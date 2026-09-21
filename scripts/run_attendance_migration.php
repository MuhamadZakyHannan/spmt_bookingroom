<?php

require_once __DIR__ . '/../config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$direction = $argv[1] ?? 'up';
if (!in_array($direction, ['up', 'down'], true)) {
    fwrite(STDERR, "Pemakaian: php scripts/run_attendance_migration.php [up|down]\n");
    exit(1);
}

if (!$pdo) {
    fwrite(STDERR, "Koneksi database tidak tersedia. Periksa konfigurasi .env/XAMPP.\n");
    exit(2);
}

$columnCheck = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'attendance_status'");
$isInstalled = (bool)$columnCheck->fetch();

if ($direction === 'up' && $isInstalled) {
    echo "Migrasi attendance sudah terpasang.\n";
    exit(0);
}

if ($direction === 'down' && !$isInstalled) {
    echo "Migrasi attendance belum terpasang; tidak ada yang di-rollback.\n";
    exit(0);
}

$file = __DIR__ . '/../migrations/20260921_attendance_phase_one_' . $direction . '.sql';
$sql = file_get_contents($file);

try {
    $pdo->exec($sql);
    echo $direction === 'up'
        ? "Migrasi attendance berhasil dipasang.\n"
        : "Migrasi attendance berhasil di-rollback.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migrasi gagal: " . $e->getMessage() . "\n");
    exit(3);
}
