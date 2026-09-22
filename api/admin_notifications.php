<?php
/**
 * API notifikasi admin: jumlah notifikasi belum dibaca dan aksi tandai dibaca.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/NotificationModel.php';

$method = ApiRequest::requireMethod('GET', 'POST');
ApiRequest::requireAdmin();

$notificationModel = new NotificationModel();
$adminUserId = (int)$_SESSION['user_id'];

if ($method === 'POST') {
    ApiRequest::requireCsrf();

    $action = $_POST['action'] ?? '';
    if ($action !== 'mark_all_read') {
        ApiResponse::error('Aksi tidak valid.', 400, 'invalid_action');
    }

    $success = $notificationModel->markAllAsRead($adminUserId);
    ApiResponse::send([
        'success' => $success,
        'unread_count' => $notificationModel->getUnreadCount($adminUserId)
    ]);
}

ApiResponse::send([
    'success' => true,
    'unread_count' => $notificationModel->getUnreadCount($adminUserId),
    'server_time' => date('H:i:s')
]);
