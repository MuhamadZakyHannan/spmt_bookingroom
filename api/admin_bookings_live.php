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

// Server-side pagination & safety limits for scalable high-volume datasets
$page = max(1, (int)($_GET['page'] ?? 1));
$limitParam = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
// Memory and payload guard: ceiling cap of 500 to prevent OOM when data reaches tens of thousands
$maxCeiling = 500;
$limit = ($limitParam > 0) ? min($limitParam, $maxCeiling) : 0;
$offset = ($page - 1) * $limit;

$totalMatching = $bookingModel->countAllBookings($search, $status);
$bookings = $bookingModel->getAllBookings($search, $status, $limit, $offset);

$conflictGroupsRaw = $bookingModel->getConflictingGroups();
$conflictBookingIds = [];
foreach ($conflictGroupsRaw as $cg) {
    foreach ($cg['bookings'] as $cb) {
        $conflictBookingIds[$cb['id']] = true;
    }
}
$usage = fn($department, $date) => $bookingModel->getDivisionMonthlyUsageCount($department, $date);
$conflictAnalyses = [];
foreach ($conflictGroupsRaw as $group) {
    $analysis = SawService::analyzeConflictGroup($group['bookings'], $usage);
    if ($analysis) {
        $conflictAnalyses[] = array_merge($group, ['saw' => $analysis]);
    }
}
$conflictCount = count($conflictAnalyses);
$hasConflicts = $conflictCount > 0;
$conflict_analyses = $conflictAnalyses;

ob_start();
require __DIR__ . '/../app/views/admin/partials/_booking_conflicts_tab.php';
$conflictsHtml = ob_get_clean();
$conflictsHash = md5($conflictsHtml);

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
        'admin_notes' => $b['admin_notes'] ?? null,
        'activity_type_label' => $actLabel,
        'document_id' => (int)($b['document_id'] ?? 0),
        'document_name' => $b['document_name'] ?? '',
        'is_conflict' => isset($conflictBookingIds[$b['id']])
    ];
}

// Generate data hash for client-side change detection
$dataHash = md5(json_encode($formattedBookings));

// Global pending count for system notification badges across all pages
$globalPendingCount = ($status === '' && $search === '' && $limit === 0) 
    ? $pendingCount 
    : $bookingModel->countAllBookings('', 'pending');

ApiResponse::send([
    'success' => true,
    'hash' => $dataHash,
    'total' => $totalMatching,
    'count' => count($formattedBookings),
    'page' => $page,
    'limit' => $limit,
    'total_pages' => $limit > 0 ? (int)ceil($totalMatching / $limit) : 1,
    'pending_count' => $globalPendingCount,
    'confirmed_count' => $confirmedCount,
    'conflicts_count' => $conflictCount,
    'conflicts_hash' => $conflictsHash,
    'conflicts_html' => $conflictsHtml,
    'server_time' => date('H:i:s'),
    'bookings' => $formattedBookings
]);
