<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/BookingModel.php';
require_once __DIR__ . '/../app/models/NotificationModel.php';
require_once __DIR__ . '/../app/services/BookingLifecycleService.php';

$db = Database::getInstance()->getConnection();

echo "Running test_admin_edit_relocate_room.php...\n";

// 1. Dapatkan test user
$userStmt = $db->query("SELECT id, name, username, department FROM users WHERE role = 'user' LIMIT 1");
$testUser = $userStmt->fetch(PDO::FETCH_ASSOC);
if (!$testUser) {
    die("[FAIL] No user found to test with.\n");
}
$userId = (int) $testUser['id'];

// Dapatkan admin user
$adminStmt = $db->query("SELECT id, name FROM users WHERE role = 'admin' LIMIT 1");
$adminUser = $adminStmt->fetch(PDO::FETCH_ASSOC);
$adminId = $adminUser ? (int) $adminUser['id'] : $userId;

// 2. Dapatkan dua ruangan aktif
$roomStmt = $db->query("SELECT id, name, capacity FROM rooms WHERE status = 'available' ORDER BY id ASC LIMIT 2");
$rooms = $roomStmt->fetchAll(PDO::FETCH_ASSOC);
if (count($rooms) < 2) {
    die("[FAIL] Need at least 2 available rooms to test.\n");
}
$roomA = $rooms[0];
$roomB = $rooms[1];

$bookingModel = new BookingModel();
$notificationModel = new NotificationModel();
$cleanupBookingIds = [];

try {
    // --- TEST 1: Admin edit confirmed booking and move to Room B with reason ---
    echo "\n--- TEST 1: Admin edit confirmed booking and move to Room B with reason ---\n";
    $testDate = '2029-05-15';
    $startTime = '10:00:00';
    $endTime = '12:00:00';

    $insertStmt = $db->prepare(
        "INSERT INTO bookings (room_id, user_id, title, purpose, date, start_time, end_time, attendees_count, status, user_name, user_dept, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, ?, NOW())"
    );
    $insertStmt->execute([
        $roomA['id'],
        $userId,
        'Rapat Koordinasi Evaluasi Program',
        'Testing admin edit relocate room',
        $testDate,
        $startTime,
        $endTime,
        15,
        $testUser['name'],
        $testUser['department'] ?? 'Divisi Umum'
    ]);
    $bookingId = (int) $db->lastInsertId();
    $cleanupBookingIds[] = $bookingId;

    $relocateReason = 'Ruangan utama sedang dipakai untuk penerimaan tamu direksi.';
    $cleanReason = trim($relocateReason);
    $notesText = 'Ruangan dialihkan dari ' . $roomA['name'] . ' ke ' . $roomB['name'] . '.' . ($cleanReason !== '' ? ' Alasan: ' . $cleanReason : '');

    // Simulasikan payload data dari form edit admin
    $editData = [
        'room_id' => (int) $roomB['id'],
        'title' => 'Rapat Koordinasi Evaluasi Program (Dipindahkan)',
        'date' => $testDate,
        'start_time' => '10:00',
        'end_time' => '12:00',
        'purpose' => 'Testing admin edit relocate room',
        'activity_type' => 'internal_divisi',
        'attendees_count' => 15,
        'user_name' => $testUser['name'],
        'user_dept' => $testUser['department'] ?? 'Divisi Umum',
        'status_reason' => BookingLifecycleService::REASON_RELOCATED_BY_ADMIN,
        'admin_notes' => $notesText,
    ];

    $updateRes = $bookingModel->updateWithSchedulePolicy(
        $bookingId,
        $editData,
        $adminId,
        true // isAdmin = true
    );

    if (empty($updateRes['success'])) {
        throw new Exception("updateWithSchedulePolicy failed: " . json_encode($updateRes));
    }
    if (empty($updateRes['is_relocated'])) {
        throw new Exception("Expected is_relocated to be true");
    }

    // Kirim notifikasi pengalihan ke user
    $notifCreated = $notificationModel->createForRelocatedBooking(
        $bookingId,
        $roomA['name'],
        $roomB['name'],
        $cleanReason
    );
    if (!$notifCreated) {
        throw new Exception("createForRelocatedBooking returned false");
    }

    // Verifikasi database
    $chkStmt = $db->prepare("SELECT room_id, status, status_reason, admin_notes FROM bookings WHERE id = ?");
    $chkStmt->execute([$bookingId]);
    $updatedBooking = $chkStmt->fetch(PDO::FETCH_ASSOC);

    if ((int) $updatedBooking['room_id'] !== (int) $roomB['id']) {
        throw new Exception("Expected room_id {$roomB['id']}, got {$updatedBooking['room_id']}");
    }
    if ($updatedBooking['status'] !== 'confirmed') {
        throw new Exception("Expected status 'confirmed', got '{$updatedBooking['status']}'");
    }
    if ($updatedBooking['status_reason'] !== BookingLifecycleService::REASON_RELOCATED_BY_ADMIN) {
        throw new Exception("Expected status_reason 'relocated_by_admin', got '{$updatedBooking['status_reason']}'");
    }
    if (strpos($updatedBooking['admin_notes'], $relocateReason) === false) {
        throw new Exception("Expected admin_notes to contain '{$relocateReason}', got '{$updatedBooking['admin_notes']}'");
    }

    echo "  [PASS] Booking moved to Room B via edit, status confirmed, status_reason relocated_by_admin, admin_notes verified.\n";

    // Verifikasi notifikasi
    $chkNotif = $db->prepare(
        "SELECT * FROM notifications WHERE recipient_user_id = ? AND booking_id = ? AND type = 'booking_relocated_by_admin' ORDER BY id DESC LIMIT 1"
    );
    $chkNotif->execute([$userId, $bookingId]);
    $notif = $chkNotif->fetch(PDO::FETCH_ASSOC);
    if (!$notif) {
        throw new Exception("Notification for relocated booking not found");
    }
    if (strpos($notif['message'], $roomB['name']) === false || strpos($notif['message'], $relocateReason) === false) {
        throw new Exception("Notification message does not include room name or reason: {$notif['message']}");
    }
    echo "  [PASS] Notification generated with new room name and relocation notes included.\n";

    // Verifikasi query service getByUserId returns admin_notes for user view
    $userBookings = $bookingModel->getByUserId($userId);
    $found = null;
    foreach ($userBookings as $ub) {
        if ((int) $ub['id'] === $bookingId) {
            $found = $ub;
            break;
        }
    }
    if (!$found || $found['admin_notes'] !== $notesText || (int) $found['room_id'] !== (int) $roomB['id']) {
        throw new Exception("getByUserId did not return expected relocated booking details");
    }
    echo "  [PASS] User can view relocated room and admin notes via getByUserId.\n";

    // --- TEST 2: Admin edit confirmed booking WITHOUT room change does not trigger relocate ---
    echo "\n--- TEST 2: Admin edit without room change ---\n";
    $noRelocateData = [
        'room_id' => (int) $roomB['id'],
        'title' => 'Rapat Koordinasi Evaluasi Program (Judul Baru)',
        'date' => $testDate,
        'start_time' => '10:00',
        'end_time' => '12:00',
        'purpose' => 'Updated title only',
        'activity_type' => 'internal_divisi',
        'attendees_count' => 15,
        'user_name' => $testUser['name'],
        'user_dept' => $testUser['department'] ?? 'Divisi Umum',
    ];
    $noRelocateRes = $bookingModel->updateWithSchedulePolicy(
        $bookingId,
        $noRelocateData,
        $adminId,
        true
    );
    if (empty($noRelocateRes['success'])) {
        throw new Exception("updateWithSchedulePolicy without room change failed");
    }
    if (!empty($noRelocateRes['is_relocated'])) {
        throw new Exception("Expected is_relocated to be false when room does not change");
    }
    echo "  [PASS] is_relocated correctly reported false when room is not changed.\n";

    echo "\nALL ADMIN EDIT RELOCATE ROOM TESTS PASSED (100% OK)!\n";
} finally {
    if (!empty($cleanupBookingIds)) {
        $inClause = implode(',', array_fill(0, count($cleanupBookingIds), '?'));
        $db->prepare("DELETE FROM notifications WHERE booking_id IN ($inClause)")->execute($cleanupBookingIds);
        $db->prepare("DELETE FROM bookings WHERE id IN ($inClause)")->execute($cleanupBookingIds);
        echo "Cleaned up test bookings.\n";
    }
}
