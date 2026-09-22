<?php
/**
 * Real-time Live Polling API Endpoint for Admin Bookings
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/BookingModel.php';

ApiRequest::requireMethod('GET');
ApiRequest::requireAdmin();

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
        'status_reason' => $b['status_reason'] ?? null,
        'activity_type_label' => $actLabel,
        'document_id' => (int)($b['document_id'] ?? 0),
        'document_name' => $b['document_name'] ?? '',
        'is_conflict' => isset($conflictBookingIds[$b['id']])
    ];
}

// Generate data hash for client-side change detection
$dataHash = md5(json_encode($formattedBookings));

ApiResponse::send([
    'success' => true,
    'hash' => $dataHash,
    'total' => count($formattedBookings),
    'pending_count' => $pendingCount,
    'confirmed_count' => $confirmedCount,
    'server_time' => date('H:i:s'),
    'bookings' => $formattedBookings
]);
