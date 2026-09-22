<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/BookingModel.php';

function expectBookingEdit(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('[FAIL] ' . $message);
    }
    echo '[PASS] ' . $message . PHP_EOL;
}

$pdo = Database::getInstance()->getConnection();
if (!$pdo) {
    throw new RuntimeException('Koneksi database tidak tersedia.');
}

$userIds = $pdo->query('SELECT id FROM users ORDER BY id LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);
$room = $pdo->query("SELECT id, capacity FROM rooms WHERE status != 'maintenance' ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (count($userIds) < 2 || !$room) {
    throw new RuntimeException('Pengujian memerlukan minimal dua pengguna dan satu ruangan aktif.');
}

$ownerId = (int) $userIds[0];
$otherUserId = (int) $userIds[1];
$roomId = (int) $room['id'];
$prefix = 'TEST-EDIT-' . bin2hex(random_bytes(4));
$testDate = '2099-12-30';
$model = new BookingModel();
$cleanup = $pdo->prepare('DELETE FROM bookings WHERE title LIKE ?');

$insert = $pdo->prepare(
    "INSERT INTO bookings
        (user_id, room_id, title, date, start_time, end_time, purpose, activity_type, attendees_count, status, user_name, user_dept)
     VALUES (?, ?, ?, ?, ?, ?, 'Temporary edit test', 'internal_divisi', 1, ?, 'Booking Edit Test', 'QA')"
);

$data = static function (string $title, string $start, string $end) use ($roomId, $testDate): array {
    return [
        'room_id' => $roomId,
        'title' => $title,
        'date' => $testDate,
        'start_time' => $start,
        'end_time' => $end,
        'purpose' => 'Updated by automated test',
        'activity_type' => 'internal_divisi',
        'attendees_count' => 1,
        'user_name' => 'Booking Edit Test',
        'user_dept' => 'QA',
    ];
};

try {
    $insert->execute([$ownerId, $roomId, $prefix . '-PENDING', $testDate, '08:00:00', '09:00:00', 'pending']);
    $pendingId = (int) $pdo->lastInsertId();
    $ownerResult = $model->updateWithSchedulePolicy(
        $pendingId,
        $data($prefix . '-PENDING-UPDATED', '09:00', '10:00'),
        $ownerId,
        false
    );
    expectBookingEdit($ownerResult['success'] === true, 'Pemilik dapat mengedit booking miliknya yang masih pending.');
    expectBookingEdit($ownerResult['status'] === 'pending', 'Edit oleh pemilik tidak mengubah status pending.');

    $storedPending = $model->getById($pendingId);
    expectBookingEdit($storedPending && $storedPending['title'] === $prefix . '-PENDING-UPDATED', 'Perubahan booking pending tersimpan.');

    $otherResult = $model->updateWithSchedulePolicy(
        $pendingId,
        $data($prefix . '-UNAUTHORIZED', '09:00', '10:00'),
        $otherUserId,
        false
    );
    expectBookingEdit($otherResult['success'] === false && $otherResult['reason'] === 'forbidden', 'Pengguna lain tidak dapat mengedit booking yang bukan miliknya.');

    $insert->execute([$ownerId, $roomId, $prefix . '-CONFIRMED', $testDate, '10:00:00', '11:00:00', 'confirmed']);
    $confirmedId = (int) $pdo->lastInsertId();
    $ownerConfirmedResult = $model->updateWithSchedulePolicy(
        $confirmedId,
        $data($prefix . '-CONFIRMED-OWNER', '11:00', '12:00'),
        $ownerId,
        false
    );
    expectBookingEdit($ownerConfirmedResult['success'] === false && $ownerConfirmedResult['reason'] === 'forbidden', 'Pemilik tidak dapat mengedit booking yang sudah terkonfirmasi.');

    $adminResult = $model->updateWithSchedulePolicy(
        $confirmedId,
        $data($prefix . '-CONFIRMED-ADMIN', '11:00', '12:00'),
        $otherUserId,
        true
    );
    expectBookingEdit($adminResult['success'] === true && $adminResult['status'] === 'confirmed', 'Admin dapat mengedit booking terkonfirmasi tanpa mengubah statusnya.');

    $insert->execute([$ownerId, $roomId, $prefix . '-BLOCKER', $testDate, '13:00:00', '14:00:00', 'confirmed']);
    $conflictResult = $model->updateWithSchedulePolicy(
        $confirmedId,
        $data($prefix . '-CONFLICT', '13:15', '13:45'),
        $otherUserId,
        true
    );
    expectBookingEdit($conflictResult['success'] === false && $conflictResult['reason'] === 'confirmed_conflict', 'Edit ditolak jika bertabrakan dengan booking terkonfirmasi lain.');

    $insert->execute([$ownerId, $roomId, $prefix . '-COMPLETED', $testDate, '15:00:00', '16:00:00', 'completed']);
    $completedId = (int) $pdo->lastInsertId();
    $completedResult = $model->updateWithSchedulePolicy(
        $completedId,
        $data($prefix . '-COMPLETED-EDIT', '16:00', '17:00'),
        $otherUserId,
        true
    );
    expectBookingEdit($completedResult['success'] === false && $completedResult['reason'] === 'forbidden', 'Booking selesai tetap terkunci meskipun diakses Admin.');
} finally {
    $cleanup->execute([$prefix . '%']);
}

echo PHP_EOL . 'Hasil: 8 lulus, 0 gagal.' . PHP_EOL;
