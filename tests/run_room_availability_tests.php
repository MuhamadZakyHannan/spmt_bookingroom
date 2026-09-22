<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/services/RoomAvailabilityService.php';
require_once __DIR__ . '/../app/models/BookingModel.php';

function expectAvailability(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException('[FAIL] ' . $message);
    }

    echo '[PASS] ' . $message . PHP_EOL;
}

function findRoomResult(array $snapshot, int $roomId): array
{
    foreach ($snapshot['rooms'] as $room) {
        if ((int) $room['id'] === $roomId) {
            return $room;
        }
    }

    throw new RuntimeException('Ruangan uji tidak ditemukan pada hasil service.');
}

$pdo = Database::getInstance()->getConnection();
if (! $pdo) {
    throw new RuntimeException('Koneksi database tidak tersedia.');
}

$userId = (int) $pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn();
$room = $pdo->query("SELECT id, capacity, status FROM rooms WHERE status != 'maintenance' ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($userId <= 0 || ! $room) {
    throw new RuntimeException('Data pengguna atau ruangan untuk pengujian tidak tersedia.');
}

$roomId = (int) $room['id'];
$originalRoomStatus = (string) $room['status'];
$testDate = '2099-12-31';
$service = new RoomAvailabilityService($pdo);
$pdo->beginTransaction();

try {
    $insert = $pdo->prepare(
        "INSERT INTO bookings
            (user_id, room_id, title, date, start_time, end_time, purpose, activity_type, attendees_count, status, user_name, user_dept)
         VALUES (?, ?, ?, ?, ?, ?, 'Temporary automated test', 'internal_divisi', 1, ?, 'Availability Test', 'QA')"
    );

    $insert->execute([$userId, $roomId, 'TEST-PENDING', $testDate, '09:00:00', '10:00:00', 'pending']);
    $pendingRoom = findRoomResult($service->getAvailability($testDate, '09:15', '09:45', 1), $roomId);
    expectAvailability($pendingRoom['availability_status'] === 'pending_conflict', 'Bentrok pending ditandai kuning sebagai pengajuan bersaing.');
    expectAvailability($pendingRoom['selectable'] === true, 'Ruangan dengan bentrok pending tetap dapat dipilih untuk analisis SAW.');

    $insert->execute([$userId, $roomId, 'TEST-CONFIRMED', $testDate, '11:00:00', '12:00:00', 'confirmed']);
    $confirmedRoom = findRoomResult($service->getAvailability($testDate, '11:15', '11:45', 1), $roomId);
    expectAvailability($confirmedRoom['availability_status'] === 'confirmed_conflict', 'Bentrok confirmed ditandai merah sebagai jadwal terpakai.');
    expectAvailability($confirmedRoom['selectable'] === false, 'Ruangan dengan bentrok confirmed tidak dapat dipilih.');

    $boundaryRoom = findRoomResult($service->getAvailability($testDate, '10:00', '11:00', 1), $roomId);
    expectAvailability($boundaryRoom['availability_status'] === 'available', 'Jadwal yang hanya bersinggungan di batas waktu tidak dianggap bentrok.');

    $capacityRoom = findRoomResult($service->getAvailability($testDate, '13:00', '14:00', ((int) $room['capacity']) + 1), $roomId);
    expectAvailability($capacityRoom['availability_status'] === 'insufficient_capacity', 'Kapasitas yang tidak mencukupi ditandai tidak memenuhi.');
    expectAvailability($capacityRoom['selectable'] === false, 'Ruangan dengan kapasitas kurang tidak dapat dipilih.');

    $maintenance = $pdo->prepare("UPDATE rooms SET status = 'maintenance' WHERE id = ?");
    $maintenance->execute([$roomId]);
    $maintenanceRoom = findRoomResult($service->getAvailability($testDate, '13:00', '14:00', 1), $roomId);
    expectAvailability($maintenanceRoom['availability_status'] === 'maintenance', 'Ruangan perawatan ditandai tidak tersedia.');
    expectAvailability($maintenanceRoom['selectable'] === false, 'Ruangan perawatan tidak dapat dipilih.');
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

expectAvailability(
    (string) $pdo->query('SELECT status FROM rooms WHERE id = ' . $roomId)->fetchColumn() === $originalRoomStatus,
    'Transaksi pengujian mengembalikan status ruangan seperti semula.'
);

$testPrefix = 'TEST-POLICY-' . bin2hex(random_bytes(4));
$cleanup = $pdo->prepare('DELETE FROM bookings WHERE title LIKE ?');

try {
    $blockerInsert = $pdo->prepare(
        "INSERT INTO bookings
            (user_id, room_id, title, date, start_time, end_time, purpose, activity_type, attendees_count, status, user_name, user_dept)
         VALUES (?, ?, ?, ?, '15:00:00', '16:00:00', 'Temporary policy test', 'internal_divisi', 1, ?, 'Policy Test', 'QA')"
    );
    $blockerInsert->execute([$userId, $roomId, $testPrefix . '-CONFIRMED', $testDate, 'confirmed']);

    $bookingModel = new BookingModel();
    $bookingData = [
        'user_id' => $userId,
        'room_id' => $roomId,
        'title' => $testPrefix . '-REQUEST',
        'date' => $testDate,
        'start_time' => '15:15',
        'end_time' => '15:45',
        'purpose' => 'Temporary policy test',
        'activity_type' => 'internal_divisi',
        'attendees_count' => 1,
        'user_name' => 'Policy Test',
        'user_dept' => 'QA',
    ];

    $blockedResult = $bookingModel->createWithSchedulePolicy($bookingData, false);
    expectAvailability($blockedResult['success'] === false && $blockedResult['reason'] === 'confirmed_conflict', 'Server menolak penyimpanan yang bertabrakan dengan booking confirmed.');

    $requestCount = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE title = ?');
    $requestCount->execute([$bookingData['title']]);
    expectAvailability((int) $requestCount->fetchColumn() === 0, 'Booking yang ditolak tidak tersimpan ke database.');

    $cleanup->execute([$testPrefix . '-CONFIRMED']);
    $blockerInsert->execute([$userId, $roomId, $testPrefix . '-PENDING', $testDate, 'pending']);
    $pendingResult = $bookingModel->createWithSchedulePolicy($bookingData, false);
    expectAvailability($pendingResult['success'] === true, 'Server menerima booking yang hanya bertabrakan dengan pengajuan pending.');
    expectAvailability($pendingResult['status'] === 'pending', 'Booking bersaing disimpan dengan status pending.');
    expectAvailability($pendingResult['pending_conflict'] === true, 'Booking bersaing ditandai untuk alur analisis SAW.');
} finally {
    $cleanup->execute([$testPrefix . '%']);
}

echo PHP_EOL . 'Hasil: 15 lulus, 0 gagal.' . PHP_EOL;
