<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            <i class="fas fa-history text-amber-500"></i> Riwayat & Laporan Pemesanan Ruangan
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Rekapitulasi lengkap agenda pertemuan, analisis penggunaan ruangan, serta ekspor laporan resmi.
        </p>
    </div>
    
    <!-- Action Exports -->
    <?php
        $exportQuery = http_build_query([
            'start_date' => $filters['start_date'] ?? '',
            'end_date' => $filters['end_date'] ?? '',
            'room_id' => $filters['room_id'] ?? '',
            'status' => $filters['status'] ?? '',
            'search' => $filters['search'] ?? ''
        ]);
    ?>
    <div class="flex flex-wrap items-center gap-2.5">
        <a href="export_bookings_pdf.php?<?php echo $exportQuery; ?>" target="_blank" class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-sm hover:shadow transition flex items-center gap-2">
            <i class="fas fa-file-pdf text-sm"></i>
            <span>Ekspor PDF (Cetak)</span>
        </a>
        <a href="export_bookings_csv.php?<?php echo $exportQuery; ?>" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-sm hover:shadow transition flex items-center gap-2">
            <i class="fas fa-file-excel text-sm"></i>
            <span>Ekspor Excel (CSV)</span>
        </a>
    </div>
</div>

<!-- Summary Metric Cards -->
<div class="grid grid-cols-1 min-[420px]:grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-brand-50 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400 flex items-center justify-center shrink-0">
            <i class="fas fa-calendar-check text-lg"></i>
        </div>
        <div>
            <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Total Rapat</div>
            <div class="text-xl font-bold text-slate-900 dark:text-white"><?php echo $summary['total_bookings']; ?></div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
            <i class="fas fa-check-circle text-lg"></i>
        </div>
        <div>
            <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Disetujui</div>
            <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400"><?php echo $summary['confirmed_count']; ?></div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
            <i class="fas fa-clock text-lg"></i>
        </div>
        <div>
            <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Total Durasi</div>
            <div class="text-xl font-bold text-slate-900 dark:text-white"><?php echo $summary['total_hours']; ?> <span class="text-xs font-normal text-slate-400">Jam</span></div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
            <i class="fas fa-users text-lg"></i>
        </div>
        <div>
            <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Total Peserta</div>
            <div class="text-xl font-bold text-slate-900 dark:text-white"><?php echo $summary['total_attendees']; ?> <span class="text-xs font-normal text-slate-400">Orang</span></div>
        </div>
    </div>
</div>

<!-- Comprehensive Filter Form with Live Autocomplete -->
<form id="historyFilterForm" method="GET" action="admin_history.php" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm mb-6">
    <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100 dark:border-slate-700">
        <div class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
            <i class="fas fa-filter text-amber-500"></i> Filter Riwayat & Parameter Laporan
        </div>
        <?php if (!empty($filters['start_date']) || !empty($filters['end_date']) || !empty($filters['room_id']) || !empty($filters['status']) || !empty($filters['search'])): ?>
            <a href="admin_history.php" class="text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 font-semibold flex items-center gap-1">
                <i class="fas fa-times-circle"></i> Reset Filter
            </a>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <!-- Start Date -->
        <div>
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" id="histStartDate" value="<?php echo htmlspecialchars($filters['start_date'] ?? ''); ?>" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-amber-500">
        </div>

        <!-- End Date -->
        <div>
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" id="histEndDate" value="<?php echo htmlspecialchars($filters['end_date'] ?? ''); ?>" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-amber-500">
        </div>

        <!-- Room Filter -->
        <div>
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Ruangan</label>
            <select name="room_id" id="histRoomSelect" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer">
                <option value="">-- Semua Ruangan --</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?php echo $r['id']; ?>" <?php echo (isset($filters['room_id']) && $filters['room_id'] == $r['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r['name']); ?> (<?php echo htmlspecialchars($r['code']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Status Filter -->
        <div>
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Status Rapat</label>
            <select name="status" id="histStatusSelect" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer">
                <option value="">-- Semua Status --</option>
                <option value="confirmed" <?php echo ($filters['status'] ?? '') === 'confirmed' ? 'selected' : ''; ?>>✓ Disetujui (Aktif)</option>
                <option value="pending" <?php echo ($filters['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>⏳ Menunggu Persetujuan</option>
                <option value="cancelled" <?php echo ($filters['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>✕ Dibatalkan / Ditolak</option>
            </select>
        </div>

        <!-- Search with Autocomplete Dropdown & Cari Button -->
        <div class="relative" id="histSearchContainer">
            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Pencarian</label>
            <div class="flex gap-1.5 relative">
                <div class="relative flex-1">
                    <input 
                        type="text" 
                        id="histSearchInput"
                        name="search" 
                        autocomplete="off"
                        placeholder="Ketik judul, PIC, divisi..." 
                        value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" 
                        class="w-full pl-3 pr-8 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition"
                    >
                    <button 
                        type="button" 
                        id="clearHistSearchBtn" 
                        onclick="clearHistSearchInput()" 
                        class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hidden text-xs" 
                        title="Hapus pencarian"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <button type="button" onclick="applyLiveHistoryFilter()" class="px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-1 shadow-sm shrink-0" title="Cari Data">
                    <i class="fas fa-search"></i>
                    <span>Cari</span>
                </button>
            </div>

            <!-- Floating Recommendations Dropdown Box -->
            <div id="histSuggestionsBox" class="absolute left-0 right-0 top-full mt-1.5 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 py-1.5 z-50 hidden max-h-64 overflow-y-auto">
                <!-- Dynamically populated with suggestion items -->
            </div>
        </div>
    </div>
</form>

<!-- Table Container -->
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm overflow-hidden mb-8">
    <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
        <div class="font-bold text-slate-800 dark:text-slate-200 text-sm flex items-center gap-2">
            <i class="fas fa-list-alt text-brand-600 dark:text-brand-400"></i> Daftar Riwayat Pemesanan (<span id="historyDataCount"><?php echo count($bookings); ?></span> Data)
        </div>
    </div>

    <div class="responsive-table-shell">
        <table class="w-full text-left border-collapse text-xs table-fixed min-w-[900px]">
            <colgroup>
                <col class="w-[26%]">
                <col class="w-[18%]">
                <col class="w-[14%]">
                <col class="w-[14%]">
                <col class="w-[10%]">
                <col class="w-[8%]">
                <col class="w-[10%]">
            </colgroup>
            <thead>
                <tr class="bg-slate-50/80 dark:bg-slate-900/60 border-b border-slate-200/80 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                    <th class="py-3.5 px-4">Agenda</th>
                    <th class="py-3.5 px-4">Pemesan & Divisi</th>
                    <th class="py-3.5 px-4">Ruangan</th>
                    <th class="py-3.5 px-4">Tanggal & Waktu</th>
                    <th class="py-3.5 px-4 text-center">Status</th>
                    <th class="py-3.5 px-4 text-center">Detail</th>
                    <th class="py-3.5 px-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody id="historyTableBody" class="divide-y divide-slate-100 dark:divide-slate-700/80">
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <i class="fas fa-inbox text-3xl mb-2 block"></i>
                            Tidak ada riwayat booking yang sesuai dengan kriteria filter.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php 
                foreach ($bookings as $b): 
                    $purposeText = $b['purpose'] ?: 'Tanpa catatan tambahan.';
                    $isExpired = is_booking_expired($b);
                    
                    // Hitung durasi jam
                    $startT = strtotime($b['date'] . ' ' . $b['start_time']);
                    $endT = strtotime($b['date'] . ' ' . $b['end_time']);
                    $durationHours = ($endT > $startT) ? round(($endT - $startT) / 3600, 1) : 0;

                    $modalPayload = [
                        'title' => $b['title'],
                        'purpose' => $purposeText,
                        'userName' => $b['user_name'],
                        'userDept' => $b['user_dept'] ?? 'Internal',
                        'userEmail' => $b['user_email'] ?? '-',
                        'roomName' => $b['room_name'],
                        'roomCode' => $b['room_code'] ?? '',
                        'date' => format_date($b['date']),
                        'time' => format_time($b['start_time']) . ' - ' . format_time($b['end_time']) . ' WIB',
                        'duration' => $durationHours . ' Jam',
                        'attendees' => $b['attendees_count'] . ' Orang',
                        'status' => $b['status'],
                        'status_reason' => $b['status_reason'] ?? null
                    ];
                ?>
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition">
                        <!-- 1. Agenda -->
                        <td class="py-3.5 px-4 overflow-hidden">
                            <div class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm truncate" title="<?php echo htmlspecialchars($b['title']); ?>">
                                <?php echo htmlspecialchars($b['title']); ?>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5" title="<?php echo htmlspecialchars($purposeText); ?>">
                                <?php echo htmlspecialchars($purposeText); ?>
                            </div>
                            <div class="text-[10px] text-slate-400 mt-1 flex items-center gap-2 truncate">
                                <span><i class="fas fa-users mr-1"></i><?php echo $b['attendees_count']; ?> Orang</span>
                                <span>• <i class="fas fa-clock mr-1"></i><?php echo $durationHours; ?> Jam</span>
                            </div>
                        </td>

                        <!-- 2. Pemesan & Divisi -->
                        <td class="py-3.5 px-4 overflow-hidden">
                            <div class="font-bold text-slate-800 dark:text-slate-200 text-xs truncate flex items-center gap-1.5" title="<?php echo htmlspecialchars($b['user_name']); ?>">
                                <i class="fas fa-user-circle text-slate-400 shrink-0"></i>
                                <span class="truncate"><?php echo htmlspecialchars($b['user_name']); ?></span>
                            </div>
                            <div class="text-[11px] text-brand-600 dark:text-brand-400 font-semibold truncate mt-0.5" title="<?php echo htmlspecialchars($b['user_dept'] ?? 'Internal'); ?>">
                                <?php echo htmlspecialchars($b['user_dept'] ?? 'Internal'); ?>
                            </div>
                            <div class="text-[10px] text-slate-400 font-mono truncate mt-0.5" title="<?php echo htmlspecialchars($b['user_email']); ?>">
                                <?php echo htmlspecialchars($b['user_email']); ?>
                            </div>
                        </td>

                        <!-- 3. Ruangan -->
                        <td class="py-3.5 px-4 overflow-hidden">
                            <div class="font-bold text-slate-800 dark:text-slate-200 text-xs truncate" title="<?php echo htmlspecialchars($b['room_name']); ?>">
                                <?php echo htmlspecialchars($b['room_name']); ?>
                            </div>
                            <div class="text-[10px] text-brand-600 dark:text-brand-400 font-mono font-semibold truncate mt-0.5">
                                [<?php echo htmlspecialchars($b['room_code'] ?? ''); ?>]
                            </div>
                        </td>

                        <!-- 4. Tanggal & Waktu -->
                        <td class="py-3.5 px-4 whitespace-nowrap overflow-hidden">
                            <div class="font-semibold text-slate-800 dark:text-slate-200 text-xs">
                                <?php echo format_date($b['date']); ?>
                            </div>
                            <div class="text-[11px] text-slate-600 dark:text-slate-400 font-mono font-bold mt-0.5 bg-slate-100 dark:bg-slate-900 px-2 py-0.5 rounded inline-block">
                                <?php echo format_time($b['start_time']); ?> - <?php echo format_time($b['end_time']); ?>
                            </div>
                        </td>

                        <!-- 5. Status -->
                        <td class="py-3.5 px-4 text-center whitespace-nowrap overflow-hidden">
                            <?php if ($b['status'] === 'confirmed'): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                    <i class="fas fa-check-circle text-emerald-600"></i> Disetujui
                                </span>
                            <?php elseif ($b['status'] === 'pending'): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-amber-50 text-amber-900 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-700">
                                    <i class="fas fa-hourglass-half text-amber-600"></i> Menunggu
                                </span>
                            <?php elseif ($isExpired): ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-700 dark:text-slate-200 dark:border-slate-600">
                                    <i class="fas fa-clock-rotate-left text-slate-500"></i> Kedaluwarsa
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">
                                    <i class="fas fa-times-circle text-rose-600"></i> Dibatalkan
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- 6. Detail -->
                        <td class="py-3.5 px-4 text-center whitespace-nowrap overflow-hidden">
                            <button 
                                type="button" 
                                onclick="openHistoryModal(<?php echo htmlspecialchars(json_encode($modalPayload)); ?>)"
                                class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg transition text-xs font-bold inline-flex items-center gap-1 shadow-sm"
                                title="Lihat Detail Lengkap"
                            >
                                <i class="fas fa-eye text-brand-600 dark:text-brand-400"></i> Detail
                            </button>
                        </td>

                        <!-- 7. Aksi -->
                        <td class="py-3.5 px-4 text-center whitespace-nowrap overflow-hidden">
                            <a 
                                href="export_bookings_pdf.php?search=<?php echo urlencode($b['title']); ?>" 
                                target="_blank"
                                class="p-1.5 px-2.5 bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/50 dark:hover:bg-brand-900/60 text-brand-600 dark:text-brand-400 border border-brand-200 dark:border-brand-800 rounded-lg transition text-xs font-bold inline-flex items-center gap-1 shadow-sm"
                                title="Cetak Slip / Laporan Pertemuan Ini"
                            >
                                <i class="fas fa-print"></i> Cetak
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detail Agenda Pertemuan -->
<div id="historyModal" class="responsive-modal fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center">
    <div class="responsive-modal-panel bg-white dark:bg-slate-800 rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl border border-slate-200 dark:border-slate-700 animate-in fade-in zoom-in-95 duration-150 space-y-4">
        <div class="flex items-start justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300">
                    Rincian Riwayat Pemesanan
                </span>
                <h3 id="histTitle" class="text-base font-bold text-slate-900 dark:text-white mt-1 break-words"></h3>
            </div>
            <button onclick="closeHistoryModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="space-y-3 text-xs text-slate-600 dark:text-slate-300">
            <!-- Catatan Pertemuan Lengkap -->
            <div class="bg-slate-50 dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Catatan / Deskripsi Agenda:</div>
                <div id="histPurpose" class="text-xs text-slate-800 dark:text-slate-200 leading-relaxed break-words break-all whitespace-pre-wrap font-medium"></div>
            </div>

            <!-- Grid Details -->
            <div class="responsive-modal-grid">
                <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] text-slate-400 block font-medium">Pemesan (PIC):</span>
                    <span id="histUser" class="font-bold text-slate-800 dark:text-slate-200 block"></span>
                    <span id="histDept" class="text-[10px] text-brand-600 dark:text-brand-400 block font-semibold"></span>
                </div>
                <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] text-slate-400 block font-medium">Ruang Rapat:</span>
                    <span id="histRoom" class="font-bold text-slate-800 dark:text-slate-200 block"></span>
                    <span id="histAttendees" class="text-[10px] text-slate-500 block"></span>
                </div>
            </div>

            <div class="responsive-modal-grid">
                <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] text-slate-400 block font-medium">Waktu & Tanggal:</span>
                    <span id="histSchedule" class="font-bold text-slate-800 dark:text-slate-200 block"></span>
                </div>
                <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] text-slate-400 block font-medium">Durasi Pertemuan:</span>
                    <span id="histDuration" class="font-bold text-slate-800 dark:text-slate-200 block"></span>
                </div>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex justify-end">
            <button onclick="closeHistoryModal()" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold rounded-xl text-xs transition">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    // In-memory dataset for instant history filtering
    const rawHistoryList = <?php echo json_encode(array_map(function($b) {
        $startT = strtotime($b['date'] . ' ' . $b['start_time']);
        $endT = strtotime($b['date'] . ' ' . $b['end_time']);
        $durationHours = ($endT > $startT) ? round(($endT - $startT) / 3600, 1) : 0;
        return [
            'id' => (int)$b['id'],
            'title' => $b['title'],
            'purpose' => $b['purpose'] ?: 'Tanpa catatan tambahan.',
            'user_name' => $b['user_name'],
            'user_dept' => $b['user_dept'] ?? 'Internal',
            'user_email' => $b['user_email'] ?? '-',
            'room_id' => (int)$b['room_id'],
            'room_name' => $b['room_name'],
            'room_code' => $b['room_code'] ?? '',
            'date' => $b['date'],
            'formatted_date' => format_date($b['date']),
            'start_time' => format_time($b['start_time']),
            'end_time' => format_time($b['end_time']),
            'duration' => $durationHours . ' Jam',
            'duration_hours' => $durationHours,
            'attendees_count' => (int)$b['attendees_count'],
            'status' => $b['status'],
            'status_reason' => $b['status_reason'] ?? null,
        ];
    }, $bookings)); ?>;

    const histSearchInput = document.getElementById('histSearchInput');
    const clearHistSearchBtn = document.getElementById('clearHistSearchBtn');
    const histSuggestionsBox = document.getElementById('histSuggestionsBox');
    const histRoomSelect = document.getElementById('histRoomSelect');
    const histStatusSelect = document.getElementById('histStatusSelect');
    const histDataCount = document.getElementById('historyDataCount');

    function openHistoryModal(data) {
        document.getElementById('histTitle').textContent = data.title;
        document.getElementById('histPurpose').textContent = data.purpose;
        document.getElementById('histUser').textContent = data.userName;
        document.getElementById('histDept').textContent = data.userDept + ' (' + data.userEmail + ')';
        document.getElementById('histRoom').textContent = data.roomName + (data.roomCode ? ' [' + data.roomCode + ']' : '');
        document.getElementById('histAttendees').textContent = 'Peserta: ' + data.attendees;
        document.getElementById('histSchedule').textContent = data.date + ' • ' + data.time;
        document.getElementById('histDuration').textContent = data.duration;
        
        const modal = document.getElementById('historyModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeHistoryModal() {
        const modal = document.getElementById('historyModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    const { escapeHtml, highlightText } = window.MeetSpaceUI;

    function clearHistSearchInput() {
        histSearchInput.value = '';
        clearHistSearchBtn.classList.add('hidden');
        histSuggestionsBox.classList.add('hidden');
        applyLiveHistoryFilter();
        histSearchInput.focus();
    }

    function selectHistSuggestion(value) {
        histSearchInput.value = value;
        histSuggestionsBox.classList.add('hidden');
        clearHistSearchBtn.classList.remove('hidden');
        applyLiveHistoryFilter();
    }

    function updateHistAutocomplete(query) {
        if (!query || query.length < 1) {
            histSuggestionsBox.innerHTML = '';
            histSuggestionsBox.classList.add('hidden');
            return;
        }

        const q = query.toLowerCase();
        const suggestions = [];
        const seen = new Set();

        rawHistoryList.forEach(b => {
            if (b.title && b.title.toLowerCase().includes(q) && !seen.has('title:' + b.title)) {
                seen.add('title:' + b.title);
                suggestions.push({ type: 'Agenda', text: b.title, icon: 'fa-calendar-alt text-amber-500' });
            }
            if (b.user_name && b.user_name.toLowerCase().includes(q) && !seen.has('user:' + b.user_name)) {
                seen.add('user:' + b.user_name);
                suggestions.push({ type: 'Pemesan', text: b.user_name, icon: 'fa-user text-blue-500' });
            }
            if (b.user_dept && b.user_dept.toLowerCase().includes(q) && !seen.has('dept:' + b.user_dept)) {
                seen.add('dept:' + b.user_dept);
                suggestions.push({ type: 'Divisi', text: b.user_dept, icon: 'fa-building text-indigo-500' });
            }
            if (b.room_name && b.room_name.toLowerCase().includes(q) && !seen.has('room:' + b.room_name)) {
                seen.add('room:' + b.room_name);
                suggestions.push({ type: 'Ruangan', text: b.room_name, icon: 'fa-door-open text-emerald-500' });
            }
        });

        if (suggestions.length === 0) {
            histSuggestionsBox.innerHTML = `
                <div class="px-3.5 py-2 text-[11px] text-slate-400 text-center">
                    Tidak ditemukan rekomendasi untuk "<strong>${escapeHtml(query)}</strong>"
                </div>
            `;
            histSuggestionsBox.classList.remove('hidden');
            return;
        }

        const topSuggestions = suggestions.slice(0, 6);
        let html = `
            <div class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-700/80 mb-1 flex items-center justify-between">
                <span>Rekomendasi Pencarian</span>
                <span class="text-[9px] font-normal text-slate-400">Klik untuk memilih</span>
            </div>
        `;

        topSuggestions.forEach(item => {
            const escapedVal = escapeHtml(item.text);
            const highlightedVal = highlightText(item.text, query);
            html += `
                <button 
                    type="button" 
                    onmousedown="selectHistSuggestion('${escapedVal.replace(/'/g, "\\'")}')" 
                    class="w-full text-left px-3.5 py-2 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition flex items-center justify-between gap-2 text-xs"
                >
                    <div class="flex items-center gap-2 truncate">
                        <i class="fas ${item.icon} text-xs shrink-0"></i>
                        <span class="truncate text-slate-800 dark:text-slate-200 font-medium">${highlightedVal}</span>
                    </div>
                    <span class="text-[9px] font-semibold px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 rounded shrink-0">
                        ${item.type}
                    </span>
                </button>
            `;
        });

        histSuggestionsBox.innerHTML = html;
        histSuggestionsBox.classList.remove('hidden');
    }

    function applyLiveHistoryFilter() {
        const query = histSearchInput.value.trim();
        const q = query.toLowerCase();
        const selectedRoom = histRoomSelect.value;
        const selectedStatus = histStatusSelect.value;

        if (query) {
            clearHistSearchBtn.classList.remove('hidden');
        } else {
            clearHistSearchBtn.classList.add('hidden');
        }

        let filtered = rawHistoryList.filter(b => {
            if (selectedRoom && b.room_id != selectedRoom) return false;
            if (selectedStatus && b.status !== selectedStatus) return false;
            if (!q) return true;

            const t = (b.title || '').toLowerCase();
            const p = (b.purpose || '').toLowerCase();
            const u = (b.user_name || '').toLowerCase();
            const d = (b.user_dept || '').toLowerCase();
            const r = (b.room_name || '').toLowerCase();
            const c = (b.room_code || '').toLowerCase();

            return t.includes(q) || p.includes(q) || u.includes(q) || d.includes(q) || r.includes(q) || c.includes(q);
        });

        // Re-ranking / Bringing matches to the TOP:
        if (q) {
            filtered.sort((a, b) => {
                const aTitle = (a.title || '').toLowerCase();
                const bTitle = (b.title || '').toLowerCase();

                let aScore = 0;
                let bScore = 0;

                if (aTitle.startsWith(q)) aScore += 100;
                else if (aTitle.includes(q)) aScore += 75;
                if ((a.purpose || '').toLowerCase().includes(q)) aScore += 40;
                if ((a.user_name || '').toLowerCase().includes(q)) aScore += 30;
                if ((a.room_name || '').toLowerCase().includes(q)) aScore += 20;

                if (bTitle.startsWith(q)) bScore += 100;
                else if (bTitle.includes(q)) bScore += 75;
                if ((b.purpose || '').toLowerCase().includes(q)) bScore += 40;
                if ((b.user_name || '').toLowerCase().includes(q)) bScore += 30;
                if ((b.room_name || '').toLowerCase().includes(q)) bScore += 20;

                if (bScore !== aScore) {
                    return bScore - aScore; // Highest match score on top
                }

                return b.id - a.id;
            });
        }

        if (histDataCount) {
            histDataCount.textContent = filtered.length;
        }

        renderHistoryTable(filtered, query);
    }

    function renderHistoryTable(bookings, highlightQuery = '') {
        const tbody = document.getElementById('historyTableBody');
        if (!bookings || bookings.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="py-12 text-center text-slate-400">
                        <i class="fas fa-inbox text-3xl mb-2 block"></i>
                        Tidak ada riwayat booking yang sesuai dengan kriteria pencarian.
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        bookings.forEach(b => {
            const purposeText = b.purpose || 'Tanpa catatan tambahan.';
            
            const modalPayload = {
                title: b.title,
                purpose: purposeText,
                userName: b.user_name,
                userDept: b.user_dept || 'Internal',
                userEmail: b.user_email || '-',
                roomName: b.room_name,
                roomCode: b.room_code || '',
                date: b.formatted_date,
                time: b.start_time + ' - ' + b.end_time + ' WIB',
                duration: b.duration,
                attendees: b.attendees_count + ' Orang',
                status: b.status
            };

            const encodedData = escapeHtml(JSON.stringify(modalPayload));

            const displayTitle = highlightText(b.title, highlightQuery);
            const displayPurpose = highlightText(purposeText, highlightQuery);
            const displayUser = highlightText(b.user_name, highlightQuery);
            const displayDept = highlightText(b.user_dept || 'Internal', highlightQuery);
            const displayRoom = highlightText(b.room_name, highlightQuery);

            let statusHtml = '';
            if (b.status === 'confirmed') {
                statusHtml = `
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                        <i class="fas fa-check-circle text-emerald-600"></i> Disetujui
                    </span>
                `;
            } else if (b.status === 'pending') {
                statusHtml = `
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-amber-50 text-amber-900 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-700">
                        <i class="fas fa-hourglass-half text-amber-600"></i> Menunggu
                    </span>
                `;
            } else if (b.status_reason === 'expired') {
                statusHtml = `
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-700 dark:text-slate-200 dark:border-slate-600">
                        <i class="fas fa-clock-rotate-left text-slate-500"></i> Kedaluwarsa
                    </span>
                `;
            } else {
                statusHtml = `
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">
                        <i class="fas fa-times-circle text-rose-600"></i> Dibatalkan
                    </span>
                `;
            }

            html += `
                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition">
                    <!-- 1. Agenda -->
                    <td class="py-3.5 px-4 overflow-hidden">
                        <div class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm truncate" title="${escapeHtml(b.title)}">
                            ${displayTitle}
                        </div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5" title="${escapeHtml(purposeText)}">
                            ${displayPurpose}
                        </div>
                        <div class="text-[10px] text-slate-400 mt-1 flex items-center gap-2 truncate">
                            <span><i class="fas fa-users mr-1"></i>${b.attendees_count} Orang</span>
                            <span>• <i class="fas fa-clock mr-1"></i>${b.duration}</span>
                        </div>
                    </td>

                    <!-- 2. Pemesan & Divisi -->
                    <td class="py-3.5 px-4 overflow-hidden">
                        <div class="font-bold text-slate-800 dark:text-slate-200 text-xs truncate flex items-center gap-1.5" title="${escapeHtml(b.user_name)}">
                            <i class="fas fa-user-circle text-slate-400 shrink-0"></i>
                            <span class="truncate">${displayUser}</span>
                        </div>
                        <div class="text-[11px] text-brand-600 dark:text-brand-400 font-semibold truncate mt-0.5" title="${escapeHtml(b.user_dept || 'Internal')}">
                            ${displayDept}
                        </div>
                        <div class="text-[10px] text-slate-400 font-mono truncate mt-0.5" title="${escapeHtml(b.user_email)}">
                            ${escapeHtml(b.user_email)}
                        </div>
                    </td>

                    <!-- 3. Ruangan -->
                    <td class="py-3.5 px-4 overflow-hidden">
                        <div class="font-bold text-slate-800 dark:text-slate-200 text-xs truncate" title="${escapeHtml(b.room_name)}">
                            ${displayRoom}
                        </div>
                        <div class="text-[10px] text-brand-600 dark:text-brand-400 font-mono font-semibold truncate mt-0.5">
                            [${escapeHtml(b.room_code)}]
                        </div>
                    </td>

                    <!-- 4. Tanggal & Waktu -->
                    <td class="py-3.5 px-4 whitespace-nowrap overflow-hidden">
                        <div class="font-semibold text-slate-800 dark:text-slate-200 text-xs">
                            ${escapeHtml(b.formatted_date)}
                        </div>
                        <div class="text-[11px] text-slate-600 dark:text-slate-400 font-mono font-bold mt-0.5 bg-slate-100 dark:bg-slate-900 px-2 py-0.5 rounded inline-block">
                            ${escapeHtml(b.start_time)} - ${escapeHtml(b.end_time)}
                        </div>
                    </td>

                    <!-- 5. Status -->
                    <td class="py-3.5 px-4 text-center whitespace-nowrap overflow-hidden">
                        ${statusHtml}
                    </td>

                    <!-- 6. Detail -->
                    <td class="py-3.5 px-4 text-center whitespace-nowrap overflow-hidden">
                        <button 
                            type="button" 
                            onclick="openHistoryModal(${encodedData})"
                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg transition text-xs font-bold inline-flex items-center gap-1 shadow-sm"
                            title="Lihat Detail Lengkap"
                        >
                            <i class="fas fa-eye text-brand-600 dark:text-brand-400"></i> Detail
                        </button>
                    </td>

                    <!-- 7. Aksi -->
                    <td class="py-3.5 px-4 text-center whitespace-nowrap overflow-hidden">
                        <a 
                            href="export_bookings_pdf.php?search=${encodeURIComponent(b.title)}" 
                            target="_blank"
                            class="p-1.5 px-2.5 bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/50 dark:hover:bg-brand-900/60 text-brand-600 dark:text-brand-400 border border-brand-200 dark:border-brand-800 rounded-lg transition text-xs font-bold inline-flex items-center gap-1 shadow-sm"
                            title="Cetak Slip / Laporan Pertemuan Ini"
                        >
                            <i class="fas fa-print"></i> Cetak
                        </a>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    document.addEventListener('DOMContentLoaded', () => {
        histSearchInput.addEventListener('input', () => {
            const q = histSearchInput.value.trim();
            updateHistAutocomplete(q);
            applyLiveHistoryFilter();
        });

        histSearchInput.addEventListener('focus', () => {
            const q = histSearchInput.value.trim();
            if (q) updateHistAutocomplete(q);
        });

        histRoomSelect.addEventListener('change', applyLiveHistoryFilter);
        histStatusSelect.addEventListener('change', applyLiveHistoryFilter);

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#histSearchContainer')) {
                histSuggestionsBox.classList.add('hidden');
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
