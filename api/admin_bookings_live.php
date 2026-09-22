<?php
/**
 * Real-time Live Polling API Endpoint for Admin Bookings
 * PT Pelabuhan Indonesia (Persero)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/BookingModel.php';

date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json');

if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$bookingModel = new BookingModel();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$bookings = $bookingModel->getAllBookings($search, $status);

$conflictGroupsRaw = $bookingModel->getConflictingGroups();
$conflictBookingIds = [];
foreach ($conflictGroupsRaw as $cg) {
    foreach ($cg['bookings'] as $cb) {
        $conflictBookingIds[$cb['id']] = true;
    }
}
$actTypes = SawService::ACTIVITY_TYPES;

$formattedBookings = [];
$pendingCount = 0;
$confirmedCount = 0;

foreach ($bookings as $b) {
    if ($b['status'] === 'pending') $pendingCount++;
    if ($b['status'] === 'confirmed') $confirmedCount++;

    $actKey = $b['activity_type'] ?? 'internal_divisi';
    $actLabel = $actTypes[$actKey]['label'] ?? 'Rapat Internal';

    $formattedBookings[] = [
        'id' => (int)$b['id'],
        'title' => $b['title'],
        'purpose' => $b['purpose'] ?: 'Tanpa catatan tambahan.',
        'user_name' => $b['user_name'],
        'user_dept' => $b['user_dept'] ?? 'Internal',
        'user_email' => $b['user_email'] ?? '-',
        'room_id' => (int)$b['room_id'],
        'room_name' => $b['room_name'],
        'room_code' => $b['room_code'] ?? '',
        'date' => $b['date'],
        'formatted_date' => format_date($b['date']),
        'start_time' => substr($b['start_time'], 0, 5),
        'end_time' => substr($b['end_time'], 0, 5),
        'attendees_count' => (int)$b['attendees_count'],
        'status' => $b['status'],
        'activity_type_label' => $actLabel,
        'is_conflict' => isset($conflictBookingIds[$b['id']])
    ];
}

// Generate data hash for client-side change detection
$dataHash = md5(json_encode($formattedBookings));

echo json_encode([
    'success' => true,
    'hash' => $dataHash,
    'total' => count($formattedBookings),
    'pending_count' => $pendingCount,
    'confirmed_count' => $confirmedCount,
    'server_time' => date('H:i:s'),
    'bookings' => $formattedBookings
]);
exit;
