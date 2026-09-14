<?php
/**
 * Export Laporan Booking CSV / Excel
 * PT Pelabuhan Indonesia (Persero)
 */

if (file_exists(__DIR__ . '/app/init.php')) {
    require_once __DIR__ . '/app/init.php';
} else {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/app/core/Database.php';
    require_once __DIR__ . '/app/models/BookingModel.php';
}

if (!is_logged_in() || !is_admin()) {
    header('Location: login.php');
    exit;
}

$bookingModel = new BookingModel();

$start_date = trim($_GET['start_date'] ?? '');
$end_date = trim($_GET['end_date'] ?? '');
$room_id = (int)($_GET['room_id'] ?? 0);
$status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$filters = [
    'start_date' => $start_date,
    'end_date' => $end_date,
    'room_id' => $room_id,
    'status' => $status,
    'search' => $search
];

$bookings = $bookingModel->getBookingHistory($filters);

$filename = 'Laporan_Pemesanan_Ruangan_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Microsoft Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header Column
fputcsv($output, [
    'No',
    'Judul Agenda / Rapat',
    'Nama Pemesan (PIC)',
    'Divisi',
    'Email Pemesan',
    'Ruang Rapat',
    'Kode Ruang',
    'Lokasi',
    'Tanggal',
    'Jam Mulai',
    'Jam Selesai',
    'Durasi (Jam)',
    'Jumlah Peserta',
    'Catatan / Keperluan',
    'Status Rapat',
    'Waktu Pengajuan'
]);

$no = 1;
foreach ($bookings as $b) {
    $startT = strtotime($b['date'] . ' ' . $b['start_time']);
    $endT = strtotime($b['date'] . ' ' . $b['end_time']);
    $dur = ($endT > $startT) ? round(($endT - $startT) / 3600, 2) : 0;

    $statusLabel = 'Dibatalkan';
    if ($b['status'] === 'confirmed') $statusLabel = 'Disetujui';
    if ($b['status'] === 'pending') $statusLabel = 'Menunggu Persetujuan';

    fputcsv($output, [
        $no++,
        $b['title'],
        $b['user_name'],
        $b['user_dept'] ?? 'Internal',
        $b['user_email'] ?? '-',
        $b['room_name'],
        $b['room_code'] ?? '-',
        $b['room_location'] ?? '-',
        $b['date'],
        substr($b['start_time'], 0, 5),
        substr($b['end_time'], 0, 5),
        $dur,
        $b['attendees_count'] ?? 1,
        $b['purpose'] ?? '-',
        $statusLabel,
        $b['created_at'] ?? '-'
    ]);
}

fclose($output);
exit;
