<?php

require_once __DIR__ . '/../config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $lifecycle = new BookingLifecycleService();
    $expiredCount = $lifecycle->expirePendingBookings();
    $completedCount = $lifecycle->completeFinishedBookings();
    echo "Pengajuan kedaluwarsa yang dibatalkan: {$expiredCount}" . PHP_EOL;
    echo "Pemesanan selesai yang diperbarui: {$completedCount}" . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Gagal memproses pengajuan kedaluwarsa: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
