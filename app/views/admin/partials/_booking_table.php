<!-- TAB 2: SEMUA PEMESANAN (REGULER) -->
<div id="tabContentAll" class="<?php echo $hasConflicts ? 'hidden' : ''; ?>">
<!-- Search & Status Filter with Live Autocomplete & Ranking -->
<form id="filterForm" onsubmit="return false;" class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm mb-6 flex flex-col sm:flex-row gap-3">
    <!-- Instant Live Search with Autocomplete Dropdown -->
    <div class="flex-1 relative" id="searchContainer">
        <i class="fas fa-search absolute left-3.5 top-3 text-slate-400 text-xs"></i>
        <input 
            type="text" 
            id="searchInput" 
            name="search" 
            autocomplete="off" 
            placeholder="Ketik untuk mencari otomatis (contoh: evaluasi, koordinasi, samudra)..." 
            value="<?php echo htmlspecialchars($search); ?>" 
            class="w-full pl-9 pr-8 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition"
        >
        <button 
            type="button" 
            id="clearSearchBtn" 
            onclick="clearSearchInput()" 
            class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hidden text-xs transition" 
            title="Hapus pencarian"
        >
            <i class="fas fa-times"></i>
        </button>

        <!-- Floating Recommendations Dropdown Box -->
        <div id="searchSuggestionsBox" class="absolute left-0 right-0 top-full mt-1.5 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 py-1.5 z-50 hidden max-h-64 overflow-y-auto">
            <!-- Dynamically populated with suggestion items -->
        </div>
    </div>

    <div class="w-full sm:w-56">
        <select id="statusSelect" name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer">
            <option value="">-- Semua Status --</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>⏳ Menunggu Persetujuan (Prioritas)</option>
            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>✓ Disetujui (Aktif)</option>
            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>✓ Selesai / No-show</option>
            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>✕ Dibatalkan / Ditolak</option>
        </select>
    </div>
    <button type="button" onclick="applyLiveFilter()" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-sm shrink-0">
        <i class="fas fa-search text-xs"></i>
        <span>Cari</span>
    </button>
</form>

<!-- Table Container -->
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm overflow-hidden mb-8">
    <div class="responsive-table-shell">
        <table class="w-full text-left border-collapse text-xs table-fixed min-w-[900px]">
            <colgroup>
                <col class="w-[24%]">
                <col class="w-[18%]">
                <col class="w-[16%]">
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
            <tbody id="bookingsTableBody" class="divide-y divide-slate-100 dark:divide-slate-700/80">
                <?php if (empty($bookings)): ?>
                    <tr id="emptyRow">
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <i class="fas fa-inbox text-3xl mb-2 block"></i>
                            Tidak ada data booking yang sesuai dengan filter.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($bookings as $b): ?>
                    <?php 
                        $isPending = ($b['status'] === 'pending'); 
                        $isExpired = is_booking_expired($b);
                        $purposeText = $b['purpose'] ?: 'Tanpa catatan tambahan.';
                        
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
                            'attendees' => $b['attendees_count'] . ' Orang',
                            'status' => $b['status'],
                            'status_reason' => $b['status_reason'] ?? null,
                            'adminNotes' => $b['admin_notes'] ?? ''
                        ];
                        $itemPayload = [
                            'id' => (int) $b['id'],
                            'title' => $b['title'],
                            'userName' => $b['user_name'],
                            'roomId' => (int) $b['room_id'],
                            'roomName' => $b['room_name'],
                            'date' => format_date($b['date']),
                            'time' => format_time($b['start_time']) . ' - ' . format_time($b['end_time']) . ' WIB',
                        ];
                        $jsonItemPayload = htmlspecialchars(json_encode($itemPayload), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr id="booking-row-<?php echo $b['id']; ?>" class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition <?php echo $isPending ? 'bg-amber-50/50 dark:bg-amber-950/25 border-l-4 border-amber-500' : ''; ?>">
                        <!-- 1. Agenda -->
                        <td class="py-3.5 px-4 overflow-hidden">
                            <div class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm truncate" title="<?php echo htmlspecialchars($b['title']); ?>">
                                <?php echo htmlspecialchars($b['title']); ?>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5" title="<?php echo htmlspecialchars($purposeText); ?>">
                                <?php echo htmlspecialchars($purposeText); ?>
                            </div>
                            <div class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                                <i class="fas fa-users text-slate-400"></i>
                                <span>Peserta: <?php echo $b['attendees_count']; ?> Orang</span>
                            </div>

                            <!-- Badges: Kriteria K1 & Status Bentrok -->
                            <div class="flex flex-wrap items-center gap-1 mt-1.5">
                                <?php 
                                    $actTypes = SawService::ACTIVITY_TYPES;
                                    $actKey = $b['activity_type'] ?? 'internal_divisi';
                                    $actInfo = $actTypes[$actKey] ?? $actTypes['internal_divisi'];
                                ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200">
                                    <i class="fas fa-tag text-[9px] text-amber-500"></i> <?php echo htmlspecialchars($actInfo['label']); ?>
                                </span>

                                <?php if (!empty($b['document_id'])): ?>
                                    <a href="booking_document.php?id=<?php echo (int) $b['document_id']; ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-violet-50 dark:bg-violet-950/50 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 transition" title="<?php echo htmlspecialchars($b['document_name']); ?>">
                                        <i class="fas fa-file-lines"></i> Surat Pendukung
                                    </a>
                                <?php endif; ?>

                                <?php if (isset($conflict_booking_ids[$b['id']])): ?>
                                    <button type="button" onclick="switchBookingTab('conflicts')" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800 hover:bg-rose-200 transition shadow-xs cursor-pointer" title="Jadwal ini bertabrakan! Klik untuk membuka analisis SAW.">
                                        <i class="fas fa-exclamation-triangle text-rose-600 animate-pulse"></i> Bentrok Jadwal (SPK SAW)
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- 2. Pemesan & Divisi -->
                        <td class="py-3.5 px-4 overflow-hidden">
                            <div class="font-bold text-slate-800 dark:text-slate-200 text-xs truncate flex items-center gap-1.5" title="<?php echo htmlspecialchars($b['user_name']); ?>">
                                <i class="fas fa-user-circle text-slate-400 shrink-0"></i>
                                <span class="truncate"><?php echo htmlspecialchars($b['user_name']); ?></span>
                            </div>
                            <div class="text-[11px] text-brand-600 dark:text-brand-400 font-semibold truncate mt-0.5" title="<?php echo htmlspecialchars($b['user_dept'] ?? 'Divisi Terkait'); ?>">
                                <?php echo htmlspecialchars($b['user_dept'] ?? 'Divisi Terkait'); ?>
                            </div>
                            <div class="text-[10px] text-slate-400 font-mono truncate mt-0.5" title="<?php echo htmlspecialchars($b['user_email']); ?>">
                                <?php echo htmlspecialchars($b['user_email']); ?>
                            </div>
                        </td>

                        <!-- 3. Ruangan -->
                        <td class="py-3.5 px-4 align-top">
                            <div class="font-bold text-slate-800 dark:text-slate-200 text-xs leading-snug break-words" title="<?php echo htmlspecialchars($b['room_name']); ?>">
                                <?php echo htmlspecialchars($b['room_name']); ?>
                            </div>
                            <div class="text-[10px] text-brand-600 dark:text-brand-400 font-mono font-semibold mt-1">
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
                            <?php echo booking_status_badge($b, 'web'); ?>
                        </td>

                        <!-- 6. Detail -->
                        <td class="py-3.5 px-4 text-center whitespace-nowrap overflow-hidden">
                            <button 
                                type="button" 
                                onclick="openDetailModal(<?php echo htmlspecialchars(json_encode($modalPayload)); ?>)"
                                class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold rounded-lg transition text-xs inline-flex items-center gap-1 shadow-sm cursor-pointer"
                                title="Lihat Detail Rapat"
                            >
                                <i class="fas fa-eye text-brand-600 dark:text-brand-400"></i> Detail
                            </button>
                        </td>

                        <!-- 7. Aksi -->
                        <td class="py-3.5 px-4 text-center whitespace-nowrap overflow-hidden">
                            <div class="flex items-center justify-center gap-1.5">
                                <?php if (in_array($b['status'], ['pending', 'confirmed'], true)): ?>
                                    <a href="edit_booking.php?id=<?php echo (int) $b['id']; ?>&amp;return_to=admin_bookings.php" class="p-1.5 px-2 bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/40 dark:hover:bg-brand-900/50 text-brand-700 dark:text-brand-300 border border-brand-200 dark:border-brand-800 font-bold rounded-lg transition text-xs flex items-center gap-1 cursor-pointer" title="Edit Booking">
                                        <i class="fas fa-pen-to-square"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if ($b['status'] === 'confirmed'): ?>
                                    <!-- Aksi BATALKAN DENGAN ALASAN -->
                                    <button type="button" onclick="openCancelModal(<?php echo $jsonItemPayload; ?>)" class="p-1.5 px-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/50 text-rose-600 dark:text-rose-300 border border-rose-200 dark:border-rose-800 font-bold rounded-lg transition text-xs flex items-center gap-1 cursor-pointer" title="Batalkan Pemesanan (Sertakan Catatan Alasan)">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                <?php elseif ($b['status'] === 'pending'): ?>
                                    <!-- Aksi TERIMA -->
                                    <form method="POST" action="admin_bookings.php" class="inline-block">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit" class="p-1.5 px-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition text-xs shadow flex items-center gap-1 cursor-pointer" title="Setujui Rapat">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>

                                    <!-- Aksi TOLAK DENGAN ALASAN -->
                                    <button type="button" onclick="openCancelModal(<?php echo $jsonItemPayload; ?>)" class="p-1.5 px-2 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold rounded-lg transition text-xs flex items-center gap-1 cursor-pointer" title="Tolak / Batalkan Pengajuan (Sertakan Catatan Alasan)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>

                                <!-- Hapus Permanen -->
                                <form method="POST" action="admin_bookings.php" class="inline-block" onsubmit="return confirm('Hapus permanen data pemesanan ini?')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" class="p-1.5 px-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition cursor-pointer" title="Hapus Permanen">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div><!-- End tabContentAll -->
