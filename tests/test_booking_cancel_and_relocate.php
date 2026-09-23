<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';

$db = Database::getInstance()->getConnection();

echo "Running test_booking_cancel_and_relocate.php...\n";

// 1. Get or create test user
$userStmt = $db->query("SELECT id, name, username FROM users WHERE role = 'user' LIMIT 1");
$testUser = $userStmt->fetch(PDO::FETCH_ASSOC);
if (!$testUser) {
    die("[FAIL] No user found to test with.\n");
}
$userId = (int) $testUser['id'];

// 2. Get two active rooms
$roomStmt = $db->query("SELECT id, name, code FROM rooms WHERE status = 'available' ORDER BY id ASC LIMIT 2");
$rooms = $roomStmt->fetchAll(PDO::FETCH_ASSOC);
if (count($rooms) < 2) {
    die("[FAIL] Need at least 2 available rooms to test relocation.\n");
}
$roomA = $rooms[0];
$roomB = $rooms[1];

$commandService = new BookingCommandService($db);
$queryService = new BookingQueryService($db);
$bookingModel = new BookingModel();
$notificationModel = new NotificationModel();

$cleanupBookingIds = [];

try {
    // --- TEST 1: Admin cancel booking with reason ---
    echo "\n--- TEST 1: Admin cancel booking with reason ---\n";
    $testDate = '2028-11-20';
    $startTime = '09:00:00';
    $endTime = '11:00:00';

    $insertStmt = $db->prepare(
        "INSERT INTO bookings (room_id, user_id, title, purpose, date, start_time, end_time, attendees_count, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
    );
    $insertStmt->execute([
        $roomA['id'],
        $userId,
        'Test Meeting Admin Cancellation',
        'Testing cancellation reason flow',
        $testDate,
        $startTime,
        $endTime,
        10
    ]);
    $booking1Id = (int) $db->lastInsertId();
    $cleanupBookingIds[] = $booking1Id;

    $cancelReason = 'Ruangan sedang maintenance sistem pendingin ruangan mendadak.';
    $cancelSuccess = $bookingModel->cancelByAdmin($booking1Id, $cancelReason);
    if (!$cancelSuccess) {
        throw new Exception("cancelByAdmin returned false");
    }

    // Verify booking row
    $chkStmt = $db->prepare("SELECT status, status_reason, admin_notes FROM bookings WHERE id = ?");
    $chkStmt->execute([$booking1Id]);
    $b1 = $chkStmt->fetch(PDO::FETCH_ASSOC);

    if ($b1['status'] !== 'cancelled') {
        throw new Exception("Expected status 'cancelled', got '{$b1['status']}'");
    }
    if ($b1['status_reason'] !== 'cancelled_by_admin') {
        throw new Exception("Expected status_reason 'cancelled_by_admin', got '{$b1['status_reason']}'");
    }
    if ($b1['admin_notes'] !== $cancelReason) {
        throw new Exception("Expected admin_notes '{$cancelReason}', got '{$b1['admin_notes']}'");
    }
    echo "  [PASS] Booking status cancelled, status_reason cancelled_by_admin, admin_notes matched.\n";

    // Verify notification creation
    $notificationModel->createForCancelledBooking($booking1Id, $cancelReason);
    $notifStmt = $db->prepare(
        "SELECT * FROM notifications WHERE recipient_user_id = ? AND booking_id = ? AND type = 'booking_cancelled_by_admin' ORDER BY id DESC LIMIT 1"
    );
    $notifStmt->execute([$userId, $booking1Id]);
    $notif1 = $notifStmt->fetch(PDO::FETCH_ASSOC);
    if (!$notif1) {
        throw new Exception("Notification for cancelled booking not found in database");
    }
    if (strpos($notif1['message'], $cancelReason) === false) {
        throw new Exception("Notification message does not include cancel reason: " . $notif1['message']);
    }
    echo "  [PASS] Notification generated with cancellation reason included.\n";

    // Verify query service getByUserId returns admin_notes
    $userBookings = $queryService->getByUserId($userId);
    $foundB1 = null;
    foreach ($userBookings as $ub) {
        if ((int)$ub['id'] === $booking1Id) {
            $foundB1 = $ub;
            break;
        }
    }
    if (!$foundB1 || $foundB1['admin_notes'] !== $cancelReason) {
        throw new Exception("getByUserId did not return expected admin_notes");
    }
    echo "  [PASS] getByUserId successfully retrieved admin_notes.\n";


    // --- TEST 2: Admin relocate room with notes ---
    echo "\n--- TEST 2: Admin relocate room with notes ---\n";
    $testDate2 = '2028-11-21';
    $startTime2 = '13:00:00';
    $endTime2 = '15:00:00';

    $insertStmt->execute([
        $roomA['id'],
        $userId,
        'Test Meeting Room Relocation',
        'Testing room change and relocation notes',
        $testDate2,
        $startTime2,
        $endTime2,
        15
    ]);
    $booking2Id = (int) $db->lastInsertId();
    $cleanupBookingIds[] = $booking2Id;

    // Confirm booking in room A first
    $db->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")->execute([$booking2Id]);

    $relocateReason = 'Dialihkan ke Ruangan B karena ruangan utama dipakai untuk kegiatan Direksi.';
    $relocateRes = $bookingModel->relocateRoom($booking2Id, (int) $roomB['id'], $relocateReason);
    if (!$relocateRes['success']) {
        throw new Exception("relocateRoom failed: " . ($relocateRes['message'] ?? 'unknown'));
    }

    // Verify booking row
    $chkStmt->execute([$booking2Id]);
    $b2 = $chkStmt->fetch(PDO::FETCH_ASSOC);
    $b2Full = $queryService->getById($booking2Id);

    if ((int)$b2Full['room_id'] !== (int)$roomB['id']) {
        throw new Exception("Expected room_id {$roomB['id']}, got {$b2Full['room_id']}");
    }
    if ($b2['status'] !== 'confirmed') {
        throw new Exception("Expected status 'confirmed', got '{$b2['status']}'");
    }
    if ($b2['status_reason'] !== 'relocated_by_admin') {
        throw new Exception("Expected status_reason 'relocated_by_admin', got '{$b2['status_reason']}'");
    }
    if (strpos($b2['admin_notes'], $relocateReason) === false) {
        throw new Exception("Expected admin_notes to contain '{$relocateReason}', got '{$b2['admin_notes']}'");
    }
    echo "  [PASS] Booking moved to Room B, status confirmed, status_reason relocated_by_admin, admin_notes matched.\n";

    // Verify notification creation for relocation
    $notificationModel->createForRelocatedBooking($booking2Id, $roomA['name'], $roomB['name'], $relocateReason);
    $notifStmt2 = $db->prepare(
        "SELECT * FROM notifications WHERE recipient_user_id = ? AND booking_id = ? AND type = 'booking_relocated_by_admin' ORDER BY id DESC LIMIT 1"
    );
    $notifStmt2->execute([$userId, $booking2Id]);
    $notif2 = $notifStmt2->fetch(PDO::FETCH_ASSOC);
    if (!$notif2) {
        throw new Exception("Notification for relocated booking not found");
    }
    if (strpos($notif2['message'], $roomB['name']) === false || strpos($notif2['message'], $relocateReason) === false) {
        throw new Exception("Notification message does not include new room or reason: " . $notif2['message']);
    }
    echo "  [PASS] Notification generated with new room name and relocation notes included.\n";


    // --- TEST 3: Conflict detection on relocation ---
    echo "\n--- TEST 3: Conflict detection on relocation ---\n";
    // Create an overlapping confirmed booking in room A for same slot as booking2
    $insertStmt->execute([
        $roomA['id'],
        $userId,
        'Occupying Meeting in Room A',
        'Will block re-relocating back to room A',
        $testDate2,
        $startTime2,
        $endTime2,
        5
    ]);
    $blockerId = (int) $db->lastInsertId();
    $cleanupBookingIds[] = $blockerId;
    $db->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")->execute([$blockerId]);

    // Try to relocate booking2 back to Room A -> must fail because of conflict!
    $conflictRes = $bookingModel->relocateRoom($booking2Id, (int) $roomA['id'], 'Attempting conflicting relocation');
    if ($conflictRes['success']) {
        throw new Exception("Expected conflict error when relocating to occupied room, but succeeded!");
    }
    echo "  [PASS] Conflict successfully detected and prevented: {$conflictRes['message']}\n";

    echo "\nALL BOOKING CANCEL & RELOCATE TESTS PASSED (100% OK)!\n";

} catch (Throwable $e) {
    echo "\n[ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
} finally {
    // Cleanup test bookings and notifications
    if (!empty($cleanupBookingIds)) {
        $inClause = implode(',', array_map('intval', $cleanupBookingIds));
        $db->query("DELETE FROM notifications WHERE booking_id IN ($inClause)");
        $db->query("DELETE FROM bookings WHERE id IN ($inClause)");
        echo "Cleaned up " . count($cleanupBookingIds) . " test bookings.\n";
    }
}
