<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';

/**
 * Menghentikan pengujian refactor domain booking ketika kontrak berubah.
 */
function expectBookingDomain(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('[FAIL] ' . $message);
    echo '[PASS] ' . $message . PHP_EOL;
}

if (!$pdo) throw new RuntimeException('Koneksi database tidak tersedia.');

$filters = [
    'start_date' => '2000-01-01',
    'end_date' => '2100-12-31',
];
$service = new BookingHistoryService($pdo);
$conflictService = new BookingConflictService($pdo);
$model = new BookingModel($pdo);

expectBookingDomain(
    $model->getBookingHistory($filters) === $service->getHistory($filters),
    'BookingModel mempertahankan hasil riwayat setelah delegasi ke service.'
);
expectBookingDomain(
    $model->getBookingHistorySummary($filters) === $service->getSummary($filters),
    'BookingModel mempertahankan kontrak ringkasan riwayat.'
);

$modelSource = (string) file_get_contents(__DIR__ . '/../app/models/BookingModel.php');
expectBookingDomain(
    str_contains($modelSource, 'bookingHistoryService()->getHistory')
        && str_contains($modelSource, 'bookingHistoryService()->getSummary')
        && (new ReflectionClass(BookingHistoryService::class))->isFinal(),
    'Query dan agregasi riwayat didelegasikan ke service khusus.'
);
expectBookingDomain(
    $model->getConflictingGroups() === $conflictService->getGroups(),
    'Deteksi kelompok konflik mempertahankan hasil setelah diekstrak.'
);
expectBookingDomain(
    str_contains($modelSource, 'bookingConflictService()->getGroups')
        && str_contains($modelSource, 'bookingConflictService()->resolve'),
    'BookingModel mendelegasikan analisis dan penyelesaian konflik.'
);

echo PHP_EOL . 'Hasil: 5 lulus, 0 gagal.' . PHP_EOL;
