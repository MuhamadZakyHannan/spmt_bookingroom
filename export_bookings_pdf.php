<?php
/**
 * Export Laporan Booking PDF / Print View
 * PT Pelabuhan Indonesia (Persero)
 */

if (file_exists(__DIR__ . '/app/init.php')) {
    require_once __DIR__ . '/app/init.php';
} else {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/app/core/Database.php';
    require_once __DIR__ . '/app/models/BookingModel.php';
    require_once __DIR__ . '/app/models/RoomModel.php';
}

if (!is_logged_in() || !is_admin()) {
    header('Location: login.php');
    exit;
}

$bookingModel = new BookingModel();
$roomModel = new RoomModel();

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

$summary = $bookingModel->getBookingHistorySummary($filters);
$bookings = $summary['bookings'];
$totalBookings = $summary['total_bookings'];
$confirmedCount = $summary['confirmed_count'];
$pendingCount = $summary['pending_count'];
$totalHours = $summary['total_hours'];
$totalAttendees = $summary['total_attendees'];

$selectedRoom = null;
if ($room_id > 0) {
    $selectedRoom = $roomModel->getById($room_id);
}

$printDate = date('d F Y, H:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS (Local Compiled Standalone) -->
    <link rel="stylesheet" href="public/css/tailwind.min.css">
    <style>
        @page {
            size: A4 landscape;
            margin: 0.8cm;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            html, body {
                background: white !important;
                color: black !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 11px;
            }
            .page-break {
                page-break-after: always;
            }
            table {
                font-size: 10px;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 p-4 sm:p-8 font-sans">

    <!-- Action Toolbar (Hidden in Print) -->
    <div class="max-w-6xl mx-auto mb-6 bg-white p-4 rounded-xl shadow border border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 no-print">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-3">
                <a href="admin_history.php" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg transition">
                    ← Kembali ke Riwayat & Laporan
                </a>
                <span class="text-xs text-slate-700 font-bold">
                    Siap dicetak atau disimpan sebagai PDF
                </span>
            </div>
            <span class="text-[11px] text-amber-700 font-medium">
                💡 <strong>Tips Cetak Bersih:</strong> Pada jendela Print browser, hilangkan centang opsi <em>"Headers and footers"</em> (Header dan footer) agar URL halaman web tidak tercetak di margin kertas.
            </span>
        </div>
        <button onclick="window.print()" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg shadow transition flex items-center gap-2 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Cetak / Simpan PDF
        </button>
    </div>

    <!-- Official Report Paper (A4 Landscape Formatted) -->
    <div class="max-w-6xl mx-auto bg-white p-8 sm:p-10 rounded-2xl shadow-lg border border-slate-200 print:shadow-none print:border-none print:p-0">
        
        <!-- Official Letterhead Header -->
        <div class="flex items-center justify-between pb-4 border-b-2 border-slate-800 mb-6">
            <div class="flex items-center gap-4">
                <img src="public/logo.png" onerror="this.style.display='none'" class="h-12 w-auto object-contain" alt="Logo">
                <div>
                    <h1 class="text-lg font-black tracking-tight text-slate-900 uppercase">PT PELABUHAN INDONESIA (PERSERO)</h1>
                    <div class="text-xs text-slate-600 font-medium tracking-wide">SISTEM RESERVASI RUANG RAPAT</div>
                    <div class="text-[10px] text-slate-500">PT Pelindo Multi Terminal Jl. Coaster No. 10, Kelurahan Tanjung Mas, Kecamatan Semarang Utara, Kota Semarang, Jawa Tengah 50174</div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm font-bold text-blue-900 uppercase">LAPORAN REKAPITULASI PEMESANAN RUANGAN</div>
                <div class="text-[11px] text-slate-500 font-mono">Dicetak: <?php echo $printDate; ?> WIB</div>
                <div class="text-[10px] text-slate-400">Oleh: <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?></div>
            </div>
        </div>

        <!-- Filter & Statistics Summary -->
        <div class="grid grid-cols-5 gap-3 mb-6 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
            <div>
                <div class="text-slate-500 font-semibold uppercase text-[10px]">Periode Tanggal</div>
                <div class="font-bold text-slate-800 mt-0.5">
                    <?php 
                    if ($start_date && $end_date) {
                        echo htmlspecialchars($start_date) . ' s/d ' . htmlspecialchars($end_date);
                    } elseif ($start_date) {
                        echo 'Mulai ' . htmlspecialchars($start_date);
                    } elseif ($end_date) {
                        echo 'Hingga ' . htmlspecialchars($end_date);
                    } else {
                        echo 'Semua Periode';
                    }
                    ?>
                </div>
            </div>
            <div>
                <div class="text-slate-500 font-semibold uppercase text-[10px]">Ruangan</div>
                <div class="font-bold text-slate-800 mt-0.5 truncate"><?php echo $selectedRoom ? htmlspecialchars($selectedRoom['name']) : 'Semua Ruangan'; ?></div>
            </div>
            <div>
                <div class="text-slate-500 font-semibold uppercase text-[10px]">Total Reservasi</div>
                <div class="font-bold text-slate-800 mt-0.5"><?php echo $totalBookings; ?> Jadwal (<?php echo $confirmedCount; ?> Sah)</div>
            </div>
            <div>
                <div class="text-slate-500 font-semibold uppercase text-[10px]">Total Durasi Rapat</div>
                <div class="font-bold text-blue-800 mt-0.5"><?php echo $totalHours; ?> Jam</div>
            </div>
            <div>
                <div class="text-slate-500 font-semibold uppercase text-[10px]">Total Peserta</div>
                <div class="font-bold text-amber-700 mt-0.5"><?php echo $totalAttendees; ?> Orang</div>
            </div>
        </div>

        <!-- Table Data -->
        <div class="overflow-x-auto mb-8">
            <table class="w-full text-left border-collapse text-[11px]">
                <thead>
                    <tr class="bg-slate-100 border-y border-slate-300 text-slate-700 font-bold uppercase tracking-wider">
                        <th class="py-2.5 px-3 w-10 text-center">No</th>
                        <th class="py-2.5 px-3">Judul Agenda / Meeting</th>
                        <th class="py-2.5 px-3">Pemesan & Divisi</th>
                        <th class="py-2.5 px-3">Ruangan</th>
                        <th class="py-2.5 px-3">Tanggal</th>
                        <th class="py-2.5 px-3">Waktu</th>
                        <th class="py-2.5 px-3 text-center">Durasi</th>
                        <th class="py-2.5 px-3 text-center">Peserta</th>
                        <th class="py-2.5 px-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="9" class="py-6 text-center text-slate-400">Tidak ada data pemesanan yang sesuai dengan filter.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($bookings as $idx => $b): 
                        $startT = strtotime($b['date'] . ' ' . $b['start_time']);
                        $endT = strtotime($b['date'] . ' ' . $b['end_time']);
                        $dur = ($endT > $startT) ? round(($endT - $startT) / 3600, 1) : 0;
                    ?>
                        <tr class="<?php echo $idx % 2 === 1 ? 'bg-slate-50/60' : 'bg-white'; ?>">
                            <td class="py-2 px-3 text-center font-mono text-slate-500"><?php echo $idx + 1; ?></td>
                            <td class="py-2 px-3">
                                <div class="font-bold text-slate-900"><?php echo htmlspecialchars($b['title']); ?></div>
                                <div class="text-[10px] text-slate-500 truncate max-w-xs"><?php echo htmlspecialchars($b['purpose'] ?: '-'); ?></div>
                            </td>
                            <td class="py-2 px-3">
                                <div class="font-semibold text-slate-800"><?php echo htmlspecialchars($b['user_name']); ?></div>
                                <div class="text-[10px] text-blue-700"><?php echo htmlspecialchars($b['user_dept'] ?? 'Internal'); ?></div>
                            </td>
                            <td class="py-2 px-3 font-semibold text-slate-800">
                                <?php echo htmlspecialchars($b['room_name']); ?>
                            </td>
                            <td class="py-2 px-3 whitespace-nowrap">
                                <?php echo htmlspecialchars($b['date']); ?>
                            </td>
                            <td class="py-2 px-3 font-mono whitespace-nowrap">
                                <?php echo substr($b['start_time'], 0, 5); ?> - <?php echo substr($b['end_time'], 0, 5); ?>
                            </td>
                            <td class="py-2 px-3 text-center font-mono text-slate-700">
                                <?php echo $dur; ?> Jam
                            </td>
                            <td class="py-2 px-3 text-center">
                                <?php echo $b['attendees_count']; ?> Org
                            </td>
                            <td class="py-2 px-3 text-right">
                                <?php if ($b['status'] === 'confirmed'): ?>
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        DISETUJUI
                                    </span>
                                <?php elseif ($b['status'] === 'pending'): ?>
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                        MENUNGGU
                                    </span>
                                <?php else: ?>
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                        BATAL
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Official Signatures (Document Validation) -->
        <div class="grid grid-cols-2 gap-8 pt-4 border-t border-slate-200 text-xs">
            <div>
                <div class="text-slate-500 font-semibold mb-1">Catatan Sistem:</div>
                <div class="text-[10px] text-slate-400 leading-relaxed">
                    Dokumen ini digenerate secara otomatis oleh Smart Meeting Room System PT Pelabuhan Indonesia (Persero). Segala perubahan jadwal yang telah disetujui harus melalui konfirmasi Administrator Ruang Rapat.
                </div>
            </div>
            <div class="flex flex-col items-end text-right">
                <div class="text-slate-600 mb-12">
                    Semarang, <?php echo date('d F Y'); ?><br>
                    <span class="font-bold text-slate-800">Mengetahui, Administrator Ruangan</span>
                </div>
                <div class="font-bold text-slate-900 border-b border-slate-400 pb-0.5 min-w-[160px] text-center">
                    ( <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator Ruangan'); ?> )
                </div>
            </div>
        </div>

    </div>

</body>
</html>
