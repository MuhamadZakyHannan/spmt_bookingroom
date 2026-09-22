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
$statisticsService = new BookingStatisticsService($pdo);
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
    str_contains($modelSource, 'history()->getHistory')
        && str_contains($modelSource, 'history()->getSummary')
        && (new ReflectionClass(BookingHistoryService::class))->isFinal(),
    'Query dan agregasi riwayat didelegasikan ke service khusus.'
);
expectBookingDomain(
    $model->getConflictingGroups() === $conflictService->getGroups(),
    'Deteksi kelompok konflik mempertahankan hasil setelah diekstrak.'
);
expectBookingDomain(
    str_contains($modelSource, 'conflicts()->getGroups')
        && str_contains($modelSource, 'conflicts()->resolve'),
    'BookingModel mendelegasikan analisis dan penyelesaian konflik.'
);
expectBookingDomain(
    $model->getStatisticsData($filters) === $statisticsService->getData($filters),
    'Dataset statistik mempertahankan kontrak setelah diekstrak.'
);
expectBookingDomain(
    str_contains($modelSource, 'schedules()->createWithPolicy')
        && str_contains($modelSource, 'queries()->getAll')
        && str_contains($modelSource, 'commands()->updateStatus'),
    'Penjadwalan, query, dan command didelegasikan ke service khusus.'
);
expectBookingDomain(
    substr_count($modelSource, PHP_EOL) < 250,
    'BookingModel menjadi facade ringkas di bawah 250 baris.'
);

echo PHP_EOL . 'Hasil: 8 lulus, 0 gagal.' . PHP_EOL;
