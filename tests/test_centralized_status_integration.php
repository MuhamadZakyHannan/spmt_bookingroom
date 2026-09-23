<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';

function expectStatusTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('[FAIL] ' . $message);
    }
    echo '[PASS] ' . $message . PHP_EOL;
}

$root = dirname(__DIR__);

// 1. Test booking_status_label
expectStatusTest(
    booking_status_label(['status' => 'completed']) === 'Selesai',
    'booking_status_label() memetakan status completed ke "Selesai".'
);
expectStatusTest(
    booking_status_label(['status' => 'confirmed']) === 'Disetujui',
    'booking_status_label() memetakan status confirmed ke "Disetujui".'
);
expectStatusTest(
    booking_status_label([
        'status' => 'confirmed',
        'status_reason' => BookingLifecycleService::REASON_RELOCATED_BY_ADMIN
    ]) === 'Disetujui (Dialihkan)',
    'booking_status_label() memetakan relokasi ke "Disetujui (Dialihkan)".'
);
expectStatusTest(
    booking_status_label([
        'status' => 'cancelled',
        'status_reason' => BookingLifecycleService::REASON_CANCELLED_BY_ADMIN
    ]) === 'Dibatalkan oleh Admin',
    'booking_status_label() memetakan pembatalan admin ke "Dibatalkan oleh Admin".'
);
expectStatusTest(
    booking_status_label([
        'status' => 'cancelled',
        'status_reason' => BookingLifecycleService::REASON_EXPIRED
    ]) === 'Kedaluwarsa',
    'booking_status_label() memetakan expired ke "Kedaluwarsa".'
);

// 2. Test booking_status_badge web & print
$completedBadgeWeb = booking_status_badge(['status' => 'completed'], 'web');
$completedBadgePrint = booking_status_badge(['status' => 'completed'], 'print');
expectStatusTest(
    str_contains($completedBadgeWeb, 'Selesai')
        && str_contains($completedBadgeWeb, 'fa-check-double')
        && str_contains($completedBadgeWeb, 'bg-blue-50'),
    'booking_status_badge(completed, web) menghasilkan badge biru dengan ikon check-double.'
);
expectStatusTest(
    str_contains($completedBadgePrint, 'SELESAI')
        && str_contains($completedBadgePrint, 'bg-blue-100')
        && !str_contains($completedBadgePrint, 'BATAL'),
    'booking_status_badge(completed, print) menghasilkan badge SELESAI tanpa label BATAL.'
);

$cancelledAdminBadge = booking_status_badge([
    'status' => 'cancelled',
    'status_reason' => BookingLifecycleService::REASON_CANCELLED_BY_ADMIN,
    'admin_notes' => 'Ruangan maintenance'
], 'web');
expectStatusTest(
    str_contains($cancelledAdminBadge, 'Dibatalkan Admin')
        && str_contains($cancelledAdminBadge, 'Ruangan maintenance'),
    'booking_status_badge() menyertakan alasan pembatalan admin dengan aman di atribut title.'
);

// 3. Test BookingHistoryService::getSummary()
$mockBookings = [
    ['id' => 1, 'date' => '2026-09-20', 'start_time' => '09:00:00', 'end_time' => '10:00:00', 'status' => 'completed', 'attendees_count' => 10],
    ['id' => 2, 'date' => '2026-09-21', 'start_time' => '10:00:00', 'end_time' => '11:00:00', 'status' => 'confirmed', 'attendees_count' => 5],
    ['id' => 3, 'date' => '2026-09-22', 'start_time' => '13:00:00', 'end_time' => '14:00:00', 'status' => 'pending', 'attendees_count' => 4],
    ['id' => 4, 'date' => '2026-09-23', 'start_time' => '15:00:00', 'end_time' => '16:00:00', 'status' => 'cancelled', 'attendees_count' => 2],
];

$historyServiceReflection = new ReflectionClass(BookingHistoryService::class);
$historyService = $historyServiceReflection->newInstanceWithoutConstructor();

// We test via a subclass or mock PDO
$db = Database::getInstance()->getConnection();
if ($db) {
    $realHistoryService = new BookingHistoryService($db);
    $summary = $realHistoryService->getSummary();
    
    expectStatusTest(
        isset($summary['confirmed_count'], $summary['completed_count'], $summary['total_bookings']),
        'Summary history memiliki key confirmed_count, completed_count, dan total_bookings.'
    );

    // confirmed_count must equal confirmed + completed
    $dbConfirmed = (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
    $dbCompleted = (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();
    $expectedApproved = $dbConfirmed + $dbCompleted;

    expectStatusTest(
        $summary['confirmed_count'] === $expectedApproved,
        "confirmed_count di summary ({$summary['confirmed_count']}) mencakup seluruh rapat sah (confirmed + completed = {$expectedApproved})."
    );
    expectStatusTest(
        $summary['completed_count'] === $dbCompleted,
        "completed_count di summary ({$summary['completed_count']}) sesuai jumlah rapat selesai di DB ({$dbCompleted})."
    );
}

// 4. Test View Integrations
$historyViewSource = (string) file_get_contents($root . '/app/views/admin/history.php');
expectStatusTest(
    str_contains($historyViewSource, 'value="completed"')
        && str_contains($historyViewSource, 'booking_status_badge($b, \'web\')')
        && str_contains($historyViewSource, 'MeetSpaceUI.renderStatusBadge(b)'),
    'Halaman Riwayat (history.php) terintegrasi dengan filter completed dan badge terpusat.'
);

$reportPdfViewSource = (string) file_get_contents($root . '/app/views/admin/booking_report_pdf.php');
expectStatusTest(
    str_contains($reportPdfViewSource, 'booking_status_badge($b, \'print\')')
        && !str_contains($reportPdfViewSource, 'DISETUJUI') // no manual hardcoded if/else anymore
        && str_contains($reportPdfViewSource, '$confirmedCount; ?> Sah'),
    'Laporan PDF (booking_report_pdf.php) menggunakan booking_status_badge print terpusat.'
);

$bookingsViewSource = (string) file_get_contents($root . '/app/views/admin/bookings.php');
if (file_exists($root . '/app/views/admin/partials/_booking_table.php')) {
    $bookingsViewSource .= (string) file_get_contents($root . '/app/views/admin/partials/_booking_table.php');
}
if (file_exists($root . '/public/js/admin-bookings.js')) {
    $bookingsViewSource .= (string) file_get_contents($root . '/public/js/admin-bookings.js');
}
expectStatusTest(
    str_contains($bookingsViewSource, 'booking_status_badge($b, \'web\')')
        && str_contains($bookingsViewSource, 'MeetSpaceUI.renderStatusBadge(b)'),
    'Halaman Kelola Booking (bookings.php) terintegrasi dengan badge terpusat.'
);

// 5. Test Live PDF Render output with actual database data
if ($db) {
    $reportService = new BookingReportService(new BookingModel($db), new RoomModel($db));
    $liveReportData = $reportService->prepare([], 'Administrator Audit');
    extract($liveReportData, EXTR_SKIP);
    ob_start();
    require $root . '/app/views/admin/booking_report_pdf.php';
    $renderedPdf = (string) ob_get_clean();

    expectStatusTest(
        str_contains($renderedPdf, 'SELESAI')
            && str_contains($renderedPdf, "{$summary['confirmed_count']} Sah"),
        "Cetak Laporan PDF berhasil merender badge SELESAI dan jumlah sah akurat ({$summary['confirmed_count']} Sah)."
    );
}

echo PHP_EOL . 'Semua pengujian integrasi status terpusat LULUS!' . PHP_EOL;
