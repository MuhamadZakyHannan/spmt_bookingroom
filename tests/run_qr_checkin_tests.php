<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/QrCheckinModel.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$passed = 0;
$failed = 0;
$roomId = 0;

function qrExpect($condition, $message) {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[PASS] $message\n";
    } else {
        $failed++;
        echo "[FAIL] $message\n";
    }
}

if (!$pdo) {
    fwrite(STDERR, "Database tidak tersedia.\n");
    exit(2);
}

try {
    $userIds = $pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
    if (count($userIds) < 2) {
        throw new RuntimeException('Test memerlukan minimal dua akun untuk memeriksa kepemilikan.');
    }

    $ownerId = (int)$userIds[0];
    $otherUserId = (int)$userIds[1];
    $suffix = strtoupper(bin2hex(random_bytes(3)));
    $roomCode = 'QR-' . $suffix;
    $displayToken = 'DISP-QR-' . $suffix;

    $roomStmt = $pdo->prepare(
        "INSERT INTO rooms (code, name, capacity, location, floor, status)
         VALUES (?, ?, 4, 'Lokasi Test', 'Lantai Test', 'available')"
    );
    $roomStmt->execute([$roomCode, 'Ruang Test QR ' . $suffix]);
    $roomId = (int)$pdo->lastInsertId();

    $displayStmt = $pdo->prepare(
        "INSERT INTO room_displays (room_id, display_name, display_token)
         VALUES (?, ?, ?)"
    );
    $displayStmt->execute([$roomId, 'Display Test QR', $displayToken]);

    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
    $start = $now->modify('-1 minute');
    $end = $now->modify('+30 minutes');
    if ($start->format('Y-m-d') !== $end->format('Y-m-d')) {
        throw new RuntimeException('Test QR tidak dijalankan dekat pergantian hari.');
    }

    $bookingStmt = $pdo->prepare(
        "INSERT INTO bookings
            (user_id, room_id, title, date, start_time, end_time, purpose,
             attendees_count, status, attendance_status, user_name, user_dept)
         VALUES (?, ?, ?, ?, ?, ?, 'Integration test QR', 1,
                 'confirmed', 'scheduled', 'Test QR', 'QA')"
    );
    $bookingStmt->execute([
        $ownerId,
        $roomId,
        'TEST-QR-' . $suffix,
        $start->format('Y-m-d'),
        $start->format('H:i:s'),
        $end->format('H:i:s'),
    ]);
    $bookingId = (int)$pdo->lastInsertId();

    $model = new QrCheckinModel();
    $issued = $model->issueForDisplay($displayToken);
    qrExpect($issued && !empty($issued['token']), 'Display terdaftar memperoleh token QR opaque.');
    qrExpect(($issued['expires_in'] ?? 0) === QrCheckinModel::TOKEN_TTL_SECONDS, 'Token memakai TTL pendek yang ditetapkan sistem.');

    $wrongOwner = $model->inspect($issued['token'], $otherUserId);
    qrExpect(!$wrongOwner['valid'], 'Akun yang bukan pemilik booking ditolak.');

    $ownerContext = $model->inspect($issued['token'], $ownerId);
    qrExpect(!empty($ownerContext['valid']), 'Pemilik booking dapat memvalidasi QR aktif.');

    $consumed = $model->consume($issued['token'], $ownerId);
    qrExpect($consumed['success'], 'Pemilik booking berhasil check-in melalui QR.');

    $replay = $model->consume($issued['token'], $ownerId);
    qrExpect(!$replay['success'], 'Token sekali pakai menolak percobaan replay.');

    $stateStmt = $pdo->prepare("SELECT attendance_status, check_in_at FROM bookings WHERE id = ?");
    $stateStmt->execute([$bookingId]);
    $state = $stateStmt->fetch();
    qrExpect(
        $state && $state['attendance_status'] === 'checked_in' && !empty($state['check_in_at']),
        'Check-in QR memperbarui status attendance booking.'
    );

    $tokenStmt = $pdo->prepare(
        "SELECT used_at, used_by_user_id FROM booking_checkin_tokens WHERE token_hash = ?"
    );
    $tokenStmt->execute([hash('sha256', $issued['token'])]);
    $tokenState = $tokenStmt->fetch();
    qrExpect(
        $tokenState && !empty($tokenState['used_at']) && (int)$tokenState['used_by_user_id'] === $ownerId,
        'Pemakaian token tercatat beserta akun pemakai.'
    );

    $noticeStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM notifications
         WHERE booking_id = ? AND type = 'attendance_check_in'"
    );
    $noticeStmt->execute([$bookingId]);
    qrExpect((int)$noticeStmt->fetchColumn() > 0, 'Check-in QR membuat notifikasi admin.');

    // Booking kedua dipakai untuk memastikan token kedaluwarsa ditolak.
    $bookingStmt->execute([
        $ownerId,
        $roomId,
        'TEST-QR-EXPIRED-' . $suffix,
        $start->format('Y-m-d'),
        $start->format('H:i:s'),
        $end->format('H:i:s'),
    ]);
    $expiredIssued = $model->issueForDisplay($displayToken);
    $expireStmt = $pdo->prepare(
        "UPDATE booking_checkin_tokens SET expires_at = DATE_SUB(NOW(), INTERVAL 1 MINUTE)
         WHERE token_hash = ?"
    );
    $expireStmt->execute([hash('sha256', $expiredIssued['token'])]);
    $expired = $model->inspect($expiredIssued['token'], $ownerId);
    qrExpect(!$expired['valid'], 'Token yang melewati TTL ditolak.');
} catch (Throwable $e) {
    $failed++;
    echo "[FAIL] Integrasi QR: " . $e->getMessage() . "\n";
} finally {
    if ($roomId > 0 && $pdo) {
        $cleanup = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        $cleanup->execute([$roomId]);
    }
}

echo "\nHasil: $passed lulus, $failed gagal.\n";
exit($failed > 0 ? 1 : 0);
