<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/core/AttendancePolicy.php';
require_once __DIR__ . '/../app/models/BookingModel.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$passed = 0;
$failed = 0;
$createdIds = [];

function expectTrue($condition, $message) {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS] $message\n";
    } else {
        $failed++;
        echo "[FAIL] $message\n";
    }
}

$timezone = new DateTimeZone('Asia/Jakarta');
$bookingTemplate = [
    'date' => '2026-09-21',
    'start_time' => '10:00:00',
    'end_time' => '11:00:00',
    'status' => 'confirmed',
    'attendance_status' => 'scheduled',
];

$tooEarly = AttendancePolicy::getActionState($bookingTemplate, new DateTimeImmutable('2026-09-21 09:44:59', $timezone));
$atOpen = AttendancePolicy::getActionState($bookingTemplate, new DateTimeImmutable('2026-09-21 09:45:00', $timezone));
$atDeadline = AttendancePolicy::getActionState($bookingTemplate, new DateTimeImmutable('2026-09-21 10:15:00', $timezone));
$tooLate = AttendancePolicy::getActionState($bookingTemplate, new DateTimeImmutable('2026-09-21 10:15:01', $timezone));

expectTrue(!$tooEarly['can_check_in'], 'Check-in belum tersedia sebelum H-15 menit.');
expectTrue($atOpen['can_check_in'], 'Check-in tersedia tepat H-15 menit.');
expectTrue($atDeadline['can_check_in'], 'Grace period mencakup tepat H+15 menit.');
expectTrue(!$tooLate['can_check_in'], 'Check-in ditolak setelah grace period.');

if (!$pdo) {
    fwrite(STDERR, "Database tidak tersedia; pengujian integrasi dilewati.\n");
    exit(2);
}

try {
    $userIds = $pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
    $roomId = (int)$pdo->query("SELECT id FROM rooms ORDER BY id ASC LIMIT 1")->fetchColumn();
    if (empty($userIds) || !$roomId) {
        throw new RuntimeException('Test memerlukan minimal satu user dan satu room.');
    }

    $ownerId = (int)$userIds[0];
    $otherUserId = isset($userIds[1]) ? (int)$userIds[1] : $ownerId + 999999;
    $model = new BookingModel();

    $now = new DateTimeImmutable('now', $timezone);
    $manualStart = $now->modify('-1 minute');
    $manualEnd = $now->modify('+30 minutes');
    if ($manualStart->format('Y-m-d') !== $manualEnd->format('Y-m-d')) {
        throw new RuntimeException('Test manual tidak dijalankan dekat pergantian hari.');
    }

    $insert = $pdo->prepare(
        "INSERT INTO bookings
            (user_id, room_id, title, date, start_time, end_time, purpose,
             attendees_count, status, attendance_status, user_name, user_dept)
         VALUES (?, ?, ?, ?, ?, ?, 'Integration test', 1, 'confirmed', 'scheduled', 'Test Attendance', 'QA')"
    );
    $insert->execute([
        $ownerId,
        $roomId,
        'TEST-ATTENDANCE-' . bin2hex(random_bytes(4)),
        $manualStart->format('Y-m-d'),
        $manualStart->format('H:i:s'),
        $manualEnd->format('H:i:s'),
    ]);
    $manualId = (int)$pdo->lastInsertId();
    $createdIds[] = $manualId;

    $unauthorized = $model->checkIn($manualId, $otherUserId);
    expectTrue(!$unauthorized['success'], 'Akun lain tidak dapat check-in booking milik pengguna.');

    $checkedIn = $model->checkIn($manualId, $ownerId);
    expectTrue($checkedIn['success'], 'Pemilik booking dapat check-in dalam jendela waktu.');

    $checkedOut = $model->checkOut($manualId, $ownerId);
    expectTrue($checkedOut['success'], 'Pemilik booking yang sudah check-in dapat check-out.');

    $stateStmt = $pdo->prepare("SELECT status, attendance_status FROM bookings WHERE id = ?");
    $stateStmt->execute([$manualId]);
    $manualState = $stateStmt->fetch();
    expectTrue(
        $manualState && $manualState['status'] === 'completed' && $manualState['attendance_status'] === 'checked_out',
        'Check-out menyimpan status completed/checked_out.'
    );

    $yesterday = $now->modify('-1 day')->format('Y-m-d');
    $insert->execute([$ownerId, $roomId, 'TEST-NOSHOW-' . bin2hex(random_bytes(4)), $yesterday, '09:00:00', '10:00:00']);
    $noShowId = (int)$pdo->lastInsertId();
    $createdIds[] = $noShowId;

    $autoInsert = $pdo->prepare(
        "INSERT INTO bookings
            (user_id, room_id, title, date, start_time, end_time, purpose,
             attendees_count, status, attendance_status, check_in_at, user_name, user_dept)
         VALUES (?, ?, ?, ?, '09:00:00', '10:00:00', 'Integration test', 1,
                 'confirmed', 'checked_in', CONCAT(?, ' 09:00:00'), 'Test Attendance', 'QA')"
    );
    $autoInsert->execute([$ownerId, $roomId, 'TEST-AUTOCHECKOUT-' . bin2hex(random_bytes(4)), $yesterday, $yesterday]);
    $autoCheckoutId = (int)$pdo->lastInsertId();
    $createdIds[] = $autoCheckoutId;

    $automatic = $model->processAutomaticAttendanceTransitions();
    expectTrue(in_array($noShowId, $automatic['no_show'], true), 'Booking lewat grace period otomatis menjadi no-show.');
    expectTrue(in_array($autoCheckoutId, $automatic['checked_out'], true), 'Booking check-in yang selesai otomatis check-out.');

    $noticeStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM notifications
         WHERE booking_id IN (?, ?, ?) AND type IN ('attendance_check_in', 'attendance_check_out', 'attendance_no_show')"
    );
    $noticeStmt->execute([$manualId, $noShowId, $autoCheckoutId]);
    expectTrue((int)$noticeStmt->fetchColumn() > 0, 'Transisi attendance membuat notifikasi admin.');
} catch (Throwable $e) {
    $failed++;
    echo "[FAIL] Integrasi database: " . $e->getMessage() . "\n";
} finally {
    if (!empty($createdIds) && $pdo) {
        $placeholders = implode(',', array_fill(0, count($createdIds), '?'));
        $cleanup = $pdo->prepare("DELETE FROM bookings WHERE id IN ($placeholders)");
        $cleanup->execute($createdIds);
    }
}

echo "\nHasil: $passed lulus, $failed gagal.\n";
exit($failed > 0 ? 1 : 0);
