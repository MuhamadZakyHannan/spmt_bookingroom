<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/services/BookingLifecycleService.php';

function expectExpiration(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException('[FAIL] ' . $message);
    echo '[PASS] ' . $message . PHP_EOL;
}

$pdo = Database::getInstance()->getConnection();
if (!$pdo) throw new RuntimeException('Koneksi database tidak tersedia.');

$userId = (int) $pdo->query('SELECT id FROM users ORDER BY id LIMIT 1')->fetchColumn();
$roomId = (int) $pdo->query("SELECT id FROM rooms WHERE status != 'maintenance' ORDER BY id LIMIT 1")->fetchColumn();
if ($userId <= 0 || $roomId <= 0) throw new RuntimeException('Data pengguna atau ruangan uji tidak tersedia.');

$prefix = 'TEST-EXPIRATION-' . bin2hex(random_bytes(4));
$insert = $pdo->prepare(
    "INSERT INTO bookings
        (user_id, room_id, title, date, start_time, end_time, purpose, activity_type, attendees_count, status, user_name, user_dept)
     VALUES (?, ?, ?, ?, ?, ?, 'Automated expiration test', 'internal_divisi', 1, ?, 'Expiration Test', 'QA')"
);

$pdo->beginTransaction();
try {
    $referenceTime = new DateTimeImmutable('2099-01-01 12:00:00');
    $baselineStatement = $pdo->prepare(
        "SELECT COUNT(*) FROM bookings
         WHERE status = 'pending' AND TIMESTAMP(date, start_time) <= ?"
    );
    $baselineStatement->execute([$referenceTime->format('Y-m-d H:i:s')]);
    $preExistingExpiredCount = (int) $baselineStatement->fetchColumn();

    $insert->execute([$userId, $roomId, $prefix . '-PAST', '2099-01-01', '09:00:00', '10:00:00', 'pending']);
    $pastId = (int) $pdo->lastInsertId();
    $insert->execute([$userId, $roomId, $prefix . '-BOUNDARY', '2099-01-01', '12:00:00', '13:00:00', 'pending']);
    $boundaryId = (int) $pdo->lastInsertId();
    $insert->execute([$userId, $roomId, $prefix . '-FUTURE', '2099-01-01', '13:00:00', '14:00:00', 'pending']);
    $futureId = (int) $pdo->lastInsertId();
    $insert->execute([$userId, $roomId, $prefix . '-CONFIRMED', '2099-01-01', '09:00:00', '10:00:00', 'confirmed']);
    $confirmedId = (int) $pdo->lastInsertId();

    $service = new BookingLifecycleService($pdo);
    $expiredCount = $service->expirePendingBookings($referenceTime);
    expectExpiration(
        $expiredCount === $preExistingExpiredCount + 2,
        'Pending sebelum atau tepat pada jam mulai otomatis diproses.'
    );

    $read = $pdo->prepare('SELECT status, status_reason FROM bookings WHERE id = ?');
    $read->execute([$pastId]);
    $past = $read->fetch(PDO::FETCH_ASSOC);
    expectExpiration($past['status'] === 'cancelled', 'Pengajuan yang melewati jam mulai otomatis dibatalkan.');
    expectExpiration($past['status_reason'] === BookingLifecycleService::REASON_EXPIRED, 'Pembatalan otomatis menyimpan alasan kedaluwarsa.');

    $read->execute([$boundaryId]);
    $boundary = $read->fetch(PDO::FETCH_ASSOC);
    expectExpiration($boundary['status_reason'] === BookingLifecycleService::REASON_EXPIRED, 'Pengajuan kedaluwarsa tepat ketika jam mulai tiba.');

    $read->execute([$futureId]);
    $future = $read->fetch(PDO::FETCH_ASSOC);
    expectExpiration($future['status'] === 'pending' && $future['status_reason'] === null, 'Pengajuan mendatang tetap menunggu persetujuan.');

    $read->execute([$confirmedId]);
    $confirmed = $read->fetch(PDO::FETCH_ASSOC);
    expectExpiration($confirmed['status'] === 'confirmed', 'Booking terkonfirmasi tidak diubah oleh proses kedaluwarsa pending.');

    expectExpiration(
        booking_status_label(['status' => 'cancelled', 'status_reason' => 'expired']) === 'Kedaluwarsa',
        'Antarmuka membedakan Kedaluwarsa dari pembatalan manual.'
    );

    $runtimeDate = (new DateTimeImmutable('yesterday'))->format('Y-m-d');
    $insert->execute([$userId, $roomId, $prefix . '-RUNTIME', $runtimeDate, '09:00:00', '10:00:00', 'pending']);
    $runtimeId = (int) $pdo->lastInsertId();
    new BookingModel();
    $read->execute([$runtimeId]);
    $runtimeBooking = $read->fetch(PDO::FETCH_ASSOC);
    expectExpiration(
        $runtimeBooking['status'] === 'cancelled'
            && $runtimeBooking['status_reason'] === BookingLifecycleService::REASON_EXPIRED,
        'Akses aplikasi turut menyelaraskan pengajuan lama tanpa menunggu tugas terjadwal.'
    );

    $modelSource = file_get_contents(__DIR__ . '/../app/models/BookingModel.php');
    $scriptSource = file_get_contents(__DIR__ . '/../scripts/expire_pending_bookings.php');
    expectExpiration(
        str_contains($modelSource, 'expirePendingBookings()')
            && str_contains($scriptSource, 'expirePendingBookings()'),
        'Sinkronisasi berjalan saat aplikasi diakses dan tersedia sebagai tugas terjadwal.'
    );
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}

echo PHP_EOL . 'Hasil: 9 lulus, 0 gagal.' . PHP_EOL;
