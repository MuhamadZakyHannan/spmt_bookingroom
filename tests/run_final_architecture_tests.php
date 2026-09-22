<?php

require_once __DIR__ . '/bootstrap.php';

/**
 * Menghentikan audit final ketika artefak lama atau batas arsitektur muncul lagi.
 */
function expectFinalArchitecture(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('[FAIL] ' . $message);
    echo '[PASS] ' . $message . PHP_EOL;
}

$root = dirname(__DIR__);
$bookingModel = (string) file_get_contents($root . '/app/models/BookingModel.php');
$adminController = (string) file_get_contents($root . '/app/controllers/AdminController.php');
$indexSource = (string) file_get_contents($root . '/index.php');
$userModel = (string) file_get_contents($root . '/app/models/UserModel.php');
$configSource = (string) file_get_contents($root . '/config.php');
$csvEntrySource = (string) file_get_contents($root . '/export_bookings_csv.php');
$pdfEntrySource = (string) file_get_contents($root . '/export_bookings_pdf.php');

expectFinalArchitecture(
    !is_file($root . '/app/init.php') && !is_file($root . '/app/core/App.php'),
    'Bootstrap dan router dinamis lama sudah dihapus.'
);
expectFinalArchitecture(
    str_contains($indexSource, "is_logged_in() ? 'dashboard.php' : 'login.php'")
        && !str_contains($indexSource, 'new App'),
    'Entry point utama hanya mengarahkan pengguna ke halaman yang tepat.'
);
expectFinalArchitecture(
    substr_count($bookingModel, PHP_EOL) < 250,
    'BookingModel tetap menjadi facade ringkas di bawah 250 baris.'
);
expectFinalArchitecture(
    substr_count($adminController, PHP_EOL) < 75,
    'AdminController tetap menjadi facade ringkas di bawah 75 baris.'
);
expectFinalArchitecture(
    !str_contains($userModel, 'findByEmail')
        && !str_contains($userModel, 'getByEmail')
        && !str_contains($bookingModel, 'getAllBookingsAdmin'),
    'Alias kompatibilitas yang tidak memiliki pemanggil sudah dihapus.'
);
expectFinalArchitecture(
    is_file($root . '/docs/ARCHITECTURE.md')
        && str_contains((string) file_get_contents($root . '/docs/ARCHITECTURE.md'), 'BookingScheduleService'),
    'Dokumentasi arsitektur menjelaskan pembagian domain terbaru.'
);
expectFinalArchitecture(
    substr_count($configSource, PHP_EOL) < 20
        && str_contains($configSource, "bootstrap/app.php"),
    'Config publik hanya menjadi bootstrap kompatibilitas yang ringkas.'
);
expectFinalArchitecture(
    substr_count($csvEntrySource, PHP_EOL) < 10
        && substr_count($pdfEntrySource, PHP_EOL) < 10
        && is_file($root . '/app/controllers/BookingExportController.php')
        && is_file($root . '/app/services/BookingReportService.php')
        && is_file($root . '/app/views/admin/booking_report_pdf.php'),
    'Entry point export hanya meneruskan request ke lapisan laporan MVC.'
);

echo PHP_EOL . 'Hasil: 8 lulus, 0 gagal.' . PHP_EOL;
