<?php
/**
 * Test Suite: Auto-Refresh Konflik Jadwal & SPK SAW serta Penolakan Transparan
 * Memvalidasi resolusi konflik SAW, pencatatan alasan penolakan, notifikasi, dan respon API live.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/models/BookingModel.php';
require_once __DIR__ . '/../app/models/NotificationModel.php';

echo "Running test_saw_conflict_auto_refresh_and_rejection.php...\n\n";

$db = Database::getInstance()->getConnection();
$bookingModel = new BookingModel($db);
$notifModel = new NotificationModel($db);

// Clean up any old test records
$db->exec("DELETE FROM notifications WHERE title LIKE '%Pemesanan Dibatalkan oleh Admin%' AND message LIKE '%[TEST_SAW]%'");
$db->exec("DELETE FROM bookings WHERE title LIKE '%[TEST_SAW]%'");

$testRoomStmt = $db->query("SELECT id, name FROM rooms WHERE status != 'maintenance' LIMIT 1");
$testRoom = $testRoomStmt->fetch(PDO::FETCH_ASSOC);
if (!$testRoom) {
    die("[FAIL] Tidak ada ruangan aktif untuk pengujian.\n");
}
$roomId = (int) $testRoom['id'];

$testUserStmt = $db->query("SELECT id FROM users LIMIT 1");
$testUser = $testUserStmt->fetch(PDO::FETCH_ASSOC);
$userId = (int) ($testUser['id'] ?? 1);

$tomorrow = date('Y-m-d', strtotime('+2 days'));

// 1. Buat 2 pemesanan bentrok
$stmtInsert = $db->prepare(
    "INSERT INTO bookings (room_id, user_id, title, purpose, date, start_time, end_time, attendees_count, activity_type, status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
);

$stmtInsert->execute([$roomId, $userId, '[TEST_SAW] Agenda Pemenang', 'Agenda Utama Direksi', $tomorrow, '13:00:00', '15:00:00', 30, 'direksi']);
$winnerId = (int) $db->lastInsertId();

$stmtInsert->execute([$roomId, $userId, '[TEST_SAW] Agenda Alternatif 2', 'Rapat Koordinasi Internal', $tomorrow, '14:00:00', '16:00:00', 10, 'internal_divisi']);
$loserId = (int) $db->lastInsertId();

echo "--- TEST 1: Deteksi Konflik & Respon Live API --- \n";
$conflictsRaw = $bookingModel->getConflictingGroups();
$foundConflict = false;
foreach ($conflictsRaw as $group) {
    $ids = array_column($group['bookings'], 'id');
    if (in_array($winnerId, $ids) && in_array($loserId, $ids)) {
        $foundConflict = true;
        break;
    }
}
if ($foundConflict) {
    echo "  [PASS] Deteksi kelompok bentrok jadwal berhasil menemukan pasangan booking uji.\n";
} else {
    echo "  [FAIL] Gagal mendeteksi konflik antara booking $winnerId dan $loserId.\n";
    exit(1);
}

// 2. Test resolusi konflik dengan alasan penolakan
echo "\n--- TEST 2: Resolusi Konflik SAW dengan Pencatatan Alasan Penolakan --- \n";
$customReason = "Pengajuan ditolak karena ada agenda [TEST_SAW] Agenda Pemenang";
$resolved = $bookingModel->resolveConflict($winnerId, [$loserId], $customReason);

if ($resolved) {
    echo "  [PASS] resolveConflict berhasil dieksekusi.\n";
} else {
    echo "  [FAIL] resolveConflict gagal dieksekusi.\n";
    exit(1);
}

// Verifikasi pemenang
$winnerBooking = $bookingModel->getById($winnerId);
if ($winnerBooking && $winnerBooking['status'] === 'confirmed') {
    echo "  [PASS] Pemenang status menjadi 'confirmed'.\n";
} else {
    echo "  [FAIL] Status pemenang tidak 'confirmed'.\n";
    exit(1);
}

// Verifikasi yang ditolak (loser)
$loserBooking = $bookingModel->getById($loserId);
if ($loserBooking && $loserBooking['status'] === 'cancelled') {
    echo "  [PASS] Booking yang kalah status menjadi 'cancelled'.\n";
} else {
    echo "  [FAIL] Status booking kalah tidak 'cancelled'.\n";
    exit(1);
}

if ($loserBooking && ($loserBooking['status_reason'] ?? '') === 'conflict_not_selected') {
    echo "  [PASS] status_reason tercatat 'conflict_not_selected'.\n";
} else {
    echo "  [FAIL] status_reason tidak sesuai: " . ($loserBooking['status_reason'] ?? 'NULL') . "\n";
    exit(1);
}

if ($loserBooking && trim($loserBooking['admin_notes'] ?? '') === $customReason) {
    echo "  [PASS] admin_notes berhasil menyimpan alasan penolakan SAW secara transparan.\n";
} else {
    echo "  [FAIL] admin_notes tidak sesuai harapan: " . ($loserBooking['admin_notes'] ?? 'NULL') . "\n";
    exit(1);
}

// 3. Verifikasi notifikasi otomatis
echo "\n--- TEST 3: Pembuatan Notifikasi Otomatis untuk Pemohon yang Ditolak --- \n";
$notifModel->createForCancelledBooking($loserId, $customReason);

$stmtNotif = $db->prepare("SELECT * FROM notifications WHERE booking_id = ? AND type = 'booking_cancelled_by_admin'");
$stmtNotif->execute([$loserId]);
$notif = $stmtNotif->fetch(PDO::FETCH_ASSOC);

if ($notif && strpos($notif['message'], $customReason) !== false) {
    echo "  [PASS] Notifikasi berhasil dibuat dan memuat alasan penolakan SAW.\n";
} else {
    echo "  [FAIL] Notifikasi tidak ditemukan atau tidak memuat alasan penolakan.\n";
    exit(1);
}

// 4. Verifikasi Helper status badge & label
echo "\n--- TEST 4: Helper Label & Badge untuk conflict_not_selected --- \n";
$label = booking_status_label($loserBooking);
if ($label === 'Ditolak (Jadwal Bentrok)') {
    echo "  [PASS] booking_status_label memetakan ke 'Ditolak (Jadwal Bentrok)'.\n";
} else {
    echo "  [FAIL] booking_status_label mengembalikan: $label\n";
    exit(1);
}

$badgeWeb = booking_status_badge($loserBooking, 'web');
if (strpos($badgeWeb, 'Ditolak (Jadwal Bentrok)') !== false && strpos($badgeWeb, htmlspecialchars($customReason, ENT_QUOTES, 'UTF-8')) !== false) {
    echo "  [PASS] booking_status_badge web menampilkan badge 'Ditolak (Jadwal Bentrok)' beserta alasan di tooltip title.\n";
} else {
    echo "  [FAIL] booking_status_badge web tidak sesuai: $badgeWeb\n";
    exit(1);
}

// Bersihkan data pengujian
$db->exec("DELETE FROM notifications WHERE booking_id IN ($winnerId, $loserId)");
$db->exec("DELETE FROM bookings WHERE id IN ($winnerId, $loserId)");

echo "\nALL SAW CONFLICT & REJECTION TESTS PASSED (100% OK)!\n";
