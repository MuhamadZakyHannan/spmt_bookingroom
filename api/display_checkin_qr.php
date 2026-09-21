<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/BookingModel.php';
require_once __DIR__ . '/../app/models/QrCheckinModel.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$displayToken = trim($_GET['token'] ?? '');
if ($displayToken === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token display wajib diisi.']);
    exit;
}

(new BookingModel())->processAutomaticAttendanceTransitions();
$issued = (new QrCheckinModel())->issueForDisplay($displayToken);

if (!$issued) {
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada booking yang sedang menunggu check-in.',
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'checkin_url' => 'qr_checkin.php?token=' . rawurlencode($issued['token']),
    'expires_at' => $issued['expires_at'],
    'expires_in' => $issued['expires_in'],
    'booking' => [
        'id' => $issued['booking_id'],
        'title' => $issued['booking_title'],
        'room_name' => $issued['room_name'],
        'start_time' => $issued['start_time'],
        'end_time' => $issued['end_time'],
    ],
]);
exit;
