<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/services/BookingLifecycleService.php';
require_once __DIR__ . '/../app/services/RoomAvailabilityService.php';
require_once __DIR__ . '/../app/models/BookingModel.php';

function expectCompleted(bool $condition, string $message): void
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

$userId = (int) $pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn();
$room = $pdo->query("SELECT id, capacity, status FROM rooms WHERE status != 'maintenance' ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($userId <= 0 || !$room) {
    throw new RuntimeException('Data pengguna atau ruangan untuk pengujian tidak tersedia.');
}

$roomId = (int) $room['id'];
$today = date('Y-m-d');
$prefix = 'TEST-COMPLETED-' . bin2hex(random_bytes(4));

$insert = $pdo->prepare(
    "INSERT INTO bookings
        (user_id, room_id, title, date, start_time, end_time, purpose, activity_type, attendees_count, status, user_name, user_dept)
     VALUES (?, ?, ?, ?, ?, ?, 'Automated lifecycle test', 'internal_divisi', 1, ?, 'Lifecycle Test', 'QA')"
);

$pdo->beginTransaction();

try {
    // 1. Uji pemesanan lewat waktu otomatis menjadi 'completed'
    $referenceTime = new DateTimeImmutable('2099-05-10 10:15:00');
    $testDate = '2099-05-10';

    // Rapat 09:00 - 10:00 (Sudah lewat waktu selesai pada 10:15)
    $insert->execute([$userId, $roomId, $prefix . '-PAST-CONFIRMED', $testDate, '09:00:00', '10:00:00', 'confirmed']);
    $pastConfirmedId = (int) $pdo->lastInsertId();

    // Rapat 11:00 - 12:00 (Mendatang, belum lewat waktu)
    $insert->execute([$userId, $roomId, $prefix . '-FUTURE-CONFIRMED', $testDate, '11:00:00', '12:00:00', 'confirmed']);
    $futureConfirmedId = (int) $pdo->lastInsertId();

    $lifecycle = new BookingLifecycleService($pdo);
    $completedCount = $lifecycle->completeFinishedBookings($referenceTime);
    expectCompleted($completedCount >= 1, 'Pemesanan yang lewat waktu selesai berhasil diperbarui.');

    $readStmt = $pdo->prepare('SELECT status FROM bookings WHERE id = ?');
    $readStmt->execute([$pastConfirmedId]);
    $pastStatus = (string) $readStmt->fetchColumn();
    expectCompleted($pastStatus === 'completed', 'Pemesanan 09:00 - 10:00 yang lewat jam 10:00 otomatis berstatus completed (selesai).');

    $readStmt->execute([$futureConfirmedId]);
    $futureStatus = (string) $readStmt->fetchColumn();
    expectCompleted($futureStatus === 'confirmed', 'Pemesanan 11:00 - 12:00 yang belum lewat waktu tetap berstatus confirmed.');

    // 2. Uji RoomAvailabilityService tidak melabeli 'Sudah terpakai' untuk jadwal yang sudah selesai
    $availabilityService = new RoomAvailabilityService($pdo);
    $availability = $availabilityService->getAvailability($testDate, '09:00', '10:00', 1);
    
    $testedRoom = null;
    foreach ($availability['rooms'] as $r) {
        if ((int) $r['id'] === $roomId) {
            $testedRoom = $r;
            break;
        }
    }
    expectCompleted($testedRoom !== null, 'Data ruangan uji ditemukan pada hasil RoomAvailabilityService.');
    expectCompleted($testedRoom['availability_status'] !== 'confirmed_conflict', 'Slot 09:00 - 10:00 yang sudah selesai TIDAK terlabel confirmed_conflict.');
    expectCompleted($testedRoom['label'] !== 'Sudah terpakai', 'Slot 09:00 - 10:00 yang sudah selesai TIDAK bertuliskan Sudah terpakai.');
    expectCompleted($testedRoom['availability_status'] === 'available', 'Ruangan berstatus available karena rapat sebelumnya telah selesai.');

    // 3. Uji pada tanggal hari ini (CURDATE())
    $insert->execute([$userId, $roomId, $prefix . '-TODAY-DONE', $today, '07:00:00', '08:00:00', 'completed']);
    $todayCompletedId = (int) $pdo->lastInsertId();

    $bookingModel = new BookingModel($pdo);
    $todayBookings = $bookingModel->getTodayBookings();
    $foundTodayCompleted = false;
    foreach ($todayBookings as $tb) {
        if ((int) $tb['id'] === $todayCompletedId) {
            $foundTodayCompleted = true;
            expectCompleted($tb['status'] === 'completed', 'Pemesanan hari ini yang selesai muncul di getTodayBookings dengan status completed.');
            break;
        }
    }
    expectCompleted($foundTodayCompleted, 'Agenda hari ini yang berstatus completed tetap muncul pada daftar Jadwal Hari Ini.');

    $todayCount = $bookingModel->getTodayActiveBookingsCount();
    expectCompleted($todayCount >= 1, 'Total Jadwal Hari Ini menghitung agenda terkonfirmasi dan selesai.');

} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo PHP_EOL . 'Semua pengujian siklus selesai otomatis (test_booking_lifecycle_completed) BERHASIL (100% PASS)!' . PHP_EOL;
