<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/BookingModel.php';
require_once __DIR__ . '/../app/models/DisplayModel.php';

function expectSchedule(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException('[FAIL] '.$message);
    }

    echo '[PASS] '.$message.PHP_EOL;
}

$pdo = Database::getInstance()->getConnection();

if (! $pdo) {
    throw new RuntimeException('Koneksi database tidak tersedia.');
}

$userId = (int) $pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn();
$roomId = (int) $pdo->query('SELECT id FROM rooms ORDER BY id LIMIT 1')->fetchColumn();
$today = (string) $pdo->query('SELECT CURDATE()')->fetchColumn();

if ($userId <= 0 || $roomId <= 0) {
    throw new RuntimeException('Data pengguna atau ruangan untuk pengujian tidak tersedia.');
}

$pdo->beginTransaction();

try {
    $title = 'TEST-SCHEDULE-'.bin2hex(random_bytes(5));
    $insert = $pdo->prepare(
        "INSERT INTO bookings
            (user_id, room_id, title, date, start_time, end_time, purpose, activity_type, attendees_count, status, user_name, user_dept)
         VALUES (?, ?, ?, ?, '09:00:00', '11:00:00', 'Temporary automated test', 'internal_divisi', 1, 'confirmed', 'Schedule Test', 'QA')"
    );
    $insert->execute([$userId, $roomId, $title, $today]);
    $bookingId = (int) $pdo->lastInsertId();

    $display = new DisplayModel();
    $live = $display->getRoomLiveStatus($roomId, '10:00:00');

    expectSchedule(($live['current_booking']['id'] ?? 0) === $bookingId, 'Monitor menandai rapat berlangsung berdasarkan waktu tanpa check-in.');
    expectSchedule(($live['status'] ?? '') === 'occupied', 'Status ruangan menjadi occupied selama jadwal berlangsung.');

    $bookings = new BookingModel();
    expectSchedule($bookings->cancel($bookingId, $userId), 'Booking confirmed dapat dibatalkan tanpa syarat attendance.');

    $afterCancel = $display->getRoomLiveStatus($roomId, '10:00:00');
    expectSchedule(($afterCancel['current_booking']['id'] ?? 0) !== $bookingId, 'Booking yang dibatalkan tidak tampil sebagai rapat berlangsung.');
} finally {
    $pdo->rollBack();
}

echo PHP_EOL.'Hasil: 4 lulus, 0 gagal.'.PHP_EOL;
