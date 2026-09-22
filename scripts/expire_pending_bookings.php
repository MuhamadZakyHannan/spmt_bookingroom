<?php

require_once __DIR__ . '/../config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $expiredCount = (new BookingLifecycleService())->expirePendingBookings();
    echo "Pengajuan kedaluwarsa yang dibatalkan: {$expiredCount}" . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Gagal memproses pengajuan kedaluwarsa: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
