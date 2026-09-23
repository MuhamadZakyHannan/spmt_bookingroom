<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/services/BookingScheduleService.php';
require_once __DIR__ . '/../app/services/BookingStatisticsService.php';
require_once __DIR__ . '/../app/controllers/BookingController.php';

function expectValidation(bool $condition, string $message): void
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

$today = date('Y-m-d');
$nowHour = (int) date('H');
$nowMinute = (int) date('i');

// --- 1. Uji Validasi di BookingController (validateBookingInput) ---
echo "\n--- 1. Uji Validasi Waktu Lampau di BookingController ---\n";
$controller = new BookingController();

// Menggunakan refleksi untuk memanggil private method validateBookingInput
$reflector = new ReflectionClass(BookingController::class);
$validateMethod = $reflector->getMethod('validateBookingInput');
$validateMethod->setAccessible(true);

// Buat waktu lampau (misal jika sekarang jam 18:00, jam 15:00 adalah lampau)
$pastHour = max(0, $nowHour - 2);
$pastTime = sprintf('%02d:00', $pastHour);
$pastEndTime = sprintf('%02d:00', $pastHour + 1);

// Jika pengujian dilakukan pada jam 00:00, gunakan 00:00 sebagai past time jika menit sekarang > 0
if ($nowHour === 0 && $nowMinute === 0) {
    $pastTime = '00:00';
    $pastEndTime = '00:30';
}

$pastInput = [
    'room_id' => 1,
    'user_name' => 'Test User',
    'user_dept' => 'SPMT - Operasi',
    'title' => 'Rapat Masa Lalu',
    'date' => $today,
    'start_time' => $pastTime,
    'end_time' => $pastEndTime,
    'purpose' => 'Pengujian waktu lampau',
    'activity_type' => 'internal_divisi',
    'attendees_count' => 5,
];

// Jalankan jika waktu lampau memang lebih kecil dari waktu sekarang
if ($pastTime <= date('H:i')) {
    $errorMsg = $validateMethod->invoke($controller, $pastInput);
    expectValidation(
        str_contains($errorMsg, 'tidak boleh mendahului waktu saat ini') && str_contains($errorMsg, 'sesuaikan jam'),
        'BookingController menolak pemesanan di hari yang sama jika jam mulai sudah terlewat dengan pesan terstruktur.'
    );
}

// Uji waktu mendatang di hari yang sama (misal 23:50 jika sekarang belum 23:50) atau besok
$futureInput = [
    'room_id' => 1,
    'user_name' => 'Test User',
    'user_dept' => 'SPMT - Operasi',
    'title' => 'Rapat Masa Depan',
    'date' => date('Y-m-d', strtotime('+1 day')),
    'start_time' => '10:00',
    'end_time' => '11:00',
    'purpose' => 'Pengujian waktu mendatang',
    'activity_type' => 'internal_divisi',
    'attendees_count' => 5,
];
$futureError = $validateMethod->invoke($controller, $futureInput);
expectValidation($futureError === '', 'Pemesanan di masa mendatang (besok) valid tanpa error.');

// --- 2. Uji Kebijakan Jadwal di BookingScheduleService ---
echo "\n--- 2. Uji Kebijakan Jadwal di BookingScheduleService ---\n";
$scheduleService = new BookingScheduleService($pdo);

if ($pastTime <= date('H:i')) {
    $policyResult = $scheduleService->createWithPolicy($pastInput, false);
    expectValidation(
        $policyResult['success'] === false && $policyResult['reason'] === 'past_time',
        'BookingScheduleService menolak penyimpanan jika jam mulai mendahului waktu saat ini.'
    );
    expectValidation(
        str_contains($policyResult['message'], 'tidak boleh mendahului waktu saat ini'),
        'Pesan penolakan BookingScheduleService terstruktur dan ramah pengguna.'
    );
}

// --- 3. Uji Perhitungan Statistik (BookingStatisticsService) ---
echo "\n--- 3. Uji Perhitungan Tingkat Persetujuan di BookingStatisticsService ---\n";
$statsService = new BookingStatisticsService($pdo);
$stats = $statsService->getData();

expectValidation(isset($stats['kpi']), 'Data KPI statistik berhasil diambil.');
expectValidation($stats['kpi']['total_bookings'] >= 0, 'Total bookings valid.');
expectValidation($stats['kpi']['confirmed_count'] >= 0, 'Confirmed count valid.');

// Uji bahwa status 'completed' dihitung sebagai bagian dari disetujui (approval_rate > 0 jika ada completed)
$completedCountDb = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();
$confirmedCountDb = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$totalApprovedDb = $completedCountDb + $confirmedCountDb;

expectValidation(
    $stats['kpi']['confirmed_count'] === $totalApprovedDb,
    "Confirmed count ($totalApprovedDb) pada KPI mencakup pemesanan 'confirmed' ($confirmedCountDb) dan 'completed' ($completedCountDb)."
);

if ($stats['kpi']['total_bookings'] > 0) {
    $expectedApprovalRate = round(($totalApprovedDb / $stats['kpi']['total_bookings']) * 100, 1);
    expectValidation(
        $stats['kpi']['approval_rate'] === $expectedApprovalRate,
        "Tingkat persetujuan ({$stats['kpi']['approval_rate']}%) dihitung secara akurat berdasarkan total disetujui."
    );
}

echo "\nSemua pengujian validasi waktu pemesanan lampau dan perbaikan statistik BERHASIL (100% PASS)!\n";
