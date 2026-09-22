<?php
/**
 * API notifikasi admin: jumlah notifikasi belum dibaca dan aksi tandai dibaca.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/NotificationModel.php';
require_once __DIR__ . '/../app/models/BookingModel.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$notificationModel = new NotificationModel();
$bookingModel = new BookingModel();
$adminUserId = (int)$_SESSION['user_id'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf_token()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF tidak valid.']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action !== 'mark_all_read') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
        exit;
    }

    $success = $notificationModel->markAllAsRead($adminUserId);
    echo json_encode([
        'success' => $success,
        'unread_count' => $notificationModel->getUnreadCount($adminUserId)
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'unread_count' => $notificationModel->getUnreadCount($adminUserId),
    'server_time' => date('H:i:s')
]);
exit;
