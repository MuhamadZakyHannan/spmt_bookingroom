<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';

/** Menghentikan pengujian laporan ketika kontrak export tidak terpenuhi. */
function expectReportExport(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('[FAIL] ' . $message);
    echo '[PASS] ' . $message . PHP_EOL;
}

$root = dirname(__DIR__);
$filters = BookingReportService::normalizeFilters([
    'start_date' => '2026-02-30',
    'end_date' => '2026-09-30',
    'room_id' => '-3',
    'status' => 'invalid-status',
    'search' => str_repeat('a', 150),
]);
expectReportExport(
    $filters['start_date'] === ''
        && $filters['end_date'] === '2026-09-30'
        && $filters['room_id'] === 0
        && $filters['status'] === ''
        && strlen($filters['search']) === 100,
    'Filter export menolak tanggal dan status tidak valid serta membatasi pencarian.'
);

$service = (new ReflectionClass(BookingReportService::class))->newInstanceWithoutConstructor();
$rows = $service->csvRows(['bookings' => [[
    'title' => '=2+2',
    'user_name' => '+Nama',
    'user_dept' => 'SPMT',
    'user_email' => 'pengguna',
    'room_name' => 'Ruang Uji',
    'room_code' => 'RU-01',
    'room_location' => 'Lantai 1',
    'date' => '2026-09-22',
    'start_time' => '10:00:00',
    'end_time' => '11:00:00',
    'attendees_count' => 5,
    'purpose' => '@formula',
    'status' => 'confirmed',
    'created_at' => '2026-09-22 08:00:00',
]]]);
expectReportExport(
    $rows[0][1] === "'=2+2"
        && $rows[0][2] === "'+Nama"
        && $rows[0][13] === "'@formula"
        && $rows[0][14] === 'Disetujui',
    'Nilai CSV tidak dapat dieksekusi sebagai formula spreadsheet.'
);

$viewSource = (string) file_get_contents($root . '/app/views/admin/booking_report_pdf.php');
$cssSource = (string) file_get_contents($root . '/src/input.css');
expectReportExport(
    !str_contains($viewSource, '<style')
        && str_contains($viewSource, 'booking-report-page')
        && str_contains($cssSource, '/* Printable booking report */'),
    'Tampilan laporan menggunakan pipeline CSS bersama tanpa blok style mandiri.'
);

$controllerSource = (string) file_get_contents($root . '/app/controllers/BookingExportController.php');
expectReportExport(
    str_contains($controllerSource, '$this->requireAdmin()')
        && str_contains($controllerSource, "Cache-Control: no-store")
        && str_contains($controllerSource, "view('admin/booking_report_pdf'"),
    'Controller export menerapkan otorisasi, kebijakan cache, dan view MVC.'
);

$database = Database::getInstance()->getConnection();
if (!$database) throw new RuntimeException('Koneksi database tidak tersedia.');
$liveReport = (new BookingReportService(
    new BookingModel($database),
    new RoomModel($database)
))->prepare([], 'Administrator Pengujian');
extract($liveReport, EXTR_SKIP);
ob_start();
require $root . '/app/views/admin/booking_report_pdf.php';
$renderedReport = (string) ob_get_clean();
expectReportExport(
    str_contains($renderedReport, 'LAPORAN REKAPITULASI PEMESANAN RUANGAN')
        && str_contains($renderedReport, 'Administrator Pengujian')
        && !str_contains(strtolower($renderedReport), 'fatal error'),
    'Dataset database dapat dirender melalui view laporan tanpa error runtime.'
);

echo PHP_EOL . 'Hasil: 5 lulus, 0 gagal.' . PHP_EOL;
