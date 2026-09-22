<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <?php if (($_SESSION['role'] ?? '') !== 'admin'): ?>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-brand-100 text-brand-700 dark:bg-brand-900/50 dark:text-brand-300 border border-brand-200 dark:border-brand-800">
                        <i class="fas fa-bookmark me-1"></i> PORTAL USER
                    </span>
                <?php endif; ?>
                <span class="text-xs text-slate-500 dark:text-slate-400">Riwayat & Status Reservasi</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Booking Saya</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pengajuan yang sedang menunggu persetujuan Admin otomatis ditampilkan di paling atas.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <span class="text-xs font-semibold px-3 py-2 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
                Total: <strong id="myBookingsCount"><?php echo count($my_bookings); ?></strong> Pemesanan
            </span>
            <a href="booking.php" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-md shadow-brand-500/20 transition text-xs">
                <i class="fas fa-plus-circle"></i> Pesan Ruangan Baru
            </a>
        </div>
    </div>

    <!-- Instant Live Search & Status Filter Form -->
    <form id="myBookingFilterForm" onsubmit="return false;" class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm flex flex-col sm:flex-row gap-3">
        <div class="flex-1 relative" id="myBookingSearchContainer">
            <i class="fas fa-search absolute left-3.5 top-3 text-slate-400 text-xs"></i>
            <input 
                type="text" 
                id="myBookingSearchInput" 
                name="search" 
                autocomplete="off" 
                placeholder="Ketik untuk mencari agenda, nama ruangan, atau catatan rapat..." 
                class="w-full pl-9 pr-8 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 transition"
            >
            <button 
                type="button" 
                id="clearMyBookingSearchBtn" 
                onclick="clearMyBookingSearch()" 
                class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hidden text-xs transition" 
                title="Hapus pencarian"
            >
                <i class="fas fa-times"></i>
            </button>

            <!-- Floating Recommendations Dropdown Box -->
            <div id="myBookingSuggestionsBox" class="absolute left-0 right-0 top-full mt-1.5 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 py-1.5 z-50 hidden max-h-64 overflow-y-auto"></div>
        </div>

        <div class="w-full sm:w-56">
            <select id="myBookingStatusFilter" name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer">
                <option value="">-- Semua Status --</option>
                <option value="pending">⏳ Menunggu Persetujuan</option>
                <option value="confirmed">✓ Disetujui (Confirmed)</option>
                <option value="completed">✓ Selesai</option>
                <option value="cancelled">✕ Dibatalkan / Ditolak</option>
            </select>
        </div>

        <button type="button" onclick="applyLiveMyBookingFilter()" class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-sm shrink-0">
            <i class="fas fa-search text-xs"></i>
            <span>Cari</span>
        </button>
    </form>

    <!-- Booking List Container -->
    <div id="myBookingsContainer" class="space-y-4">
        <?php if (empty($my_bookings)): ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 p-12 text-center shadow-sm">
                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700/60 text-slate-400 dark:text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Belum Ada Pemesanan</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1 mb-4">Anda belum memiliki riwayat pemesanan ruang rapat. Buat pengajuan pemesanan pertama Anda sekarang.</p>
                <a href="booking.php" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg text-xs shadow-md transition">
                    <i class="fas fa-calendar-plus"></i> Buat Booking
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($my_bookings as $b): ?>
                <?php 
                    $isConfirmed = ($b['status'] === 'confirmed');
                    $isPending = ($b['status'] === 'pending');
                    $isCompleted = ($b['status'] === 'completed');
                    $isCancelled = ($b['status'] === 'cancelled');
                    $purposeText = $b['purpose'] ?: 'Tidak ada catatan agenda tambahan.';
                    $isLong = strlen($purposeText) > 60;
                    $borderColor = $isPending ? 'border-l-amber-500' : ($isConfirmed ? 'border-l-emerald-500' : ($isCompleted ? 'border-l-blue-500' : 'border-l-rose-500'));
                ?>
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 border-l-4 <?php echo $borderColor; ?> shadow-sm p-4 sm:p-5 hover:shadow-md transition <?php echo $isPending ? 'bg-amber-50/20 dark:bg-amber-950/10' : ''; ?>">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- Content -->
                        <div class="space-y-2 flex-grow">
                            <div class="flex flex-wrap items-center gap-2">
                                <?php if ($isPending): ?>
                                    <span class="px-2.5 py-1 bg-amber-100 dark:bg-amber-950/60 text-amber-900 dark:text-amber-300 border border-amber-300 dark:border-amber-700 rounded-lg font-bold text-[10px] uppercase flex items-center gap-1 animate-pulse shadow-sm">
                                        <i class="fas fa-hourglass-half text-amber-600"></i> Menunggu Persetujuan Admin
                                    </span>
                                <?php elseif ($isConfirmed): ?>
                                    <span class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-lg font-bold text-[10px] uppercase flex items-center gap-1">
                                        <i class="fas fa-check-circle text-emerald-600"></i> Disetujui (Confirmed)
                                    </span>
                                <?php elseif ($isCompleted): ?>
                                    <span class="px-2.5 py-1 bg-blue-50 dark:bg-blue-950/60 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-lg font-bold text-[10px] uppercase flex items-center gap-1">
                                        <i class="fas fa-check-double text-blue-600"></i> Selesai
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-lg font-bold text-[10px] uppercase flex items-center gap-1">
                                        <i class="fas fa-times-circle text-rose-600"></i> Dibatalkan / Ditolak
                                    </span>
                                <?php endif; ?>
                                <span class="text-[11px] text-slate-400 font-mono">ID: #<?php echo $b['id']; ?></span>
                            </div>

                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($b['title']); ?></h3>
                                <div class="text-xs text-brand-600 dark:text-brand-400 font-semibold flex items-center gap-1 mt-0.5">
                                    <i class="fas fa-door-open"></i> <?php echo htmlspecialchars($b['room_name']); ?>
                                    <span class="text-slate-400 font-mono font-normal">(<?php echo htmlspecialchars($b['room_code']); ?> - <?php echo htmlspecialchars($b['location']); ?>)</span>
                                </div>
                            </div>

                            <!-- Meta Pills -->
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <div class="px-2.5 py-1 bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-100 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                                    <i class="far fa-calendar text-brand-500"></i>
                                    <span><?php echo format_date($b['date']); ?></span>
                                </div>
                                <div class="px-2.5 py-1 bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-100 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 flex items-center gap-1.5 font-mono">
                                    <i class="far fa-clock text-brand-500"></i>
                                    <span><?php echo format_time($b['start_time']); ?> - <?php echo format_time($b['end_time']); ?></span>
                                </div>
                                <div class="px-2.5 py-1 bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-100 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                                    <i class="fas fa-users text-brand-500"></i>
                                    <span><?php echo $b['attendees_count']; ?> Peserta</span>
                                </div>
                            </div>

                            <!-- Catatan Agenda with Baca Selengkapnya -->
                            <?php if (!empty($b['purpose'])): ?>
                                <div class="text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/60 p-3 rounded-xl border border-slate-200/80 dark:border-slate-700/80 mt-2">
                                    <div class="font-semibold text-[10px] uppercase text-slate-400 tracking-wider mb-0.5">Catatan Agenda:</div>
                                    <div class="<?php echo $isLong ? 'line-clamp-2' : ''; ?>">
                                        <?php echo nl2br(htmlspecialchars($purposeText)); ?>
                                    </div>
                                    <?php if ($isLong): ?>
                                        <button 
                                            type="button" 
                                            onclick="openDetailModal(<?php echo htmlspecialchars(json_encode([
                                                'title' => $b['title'],
                                                'purpose' => $purposeText,
                                                'roomName' => $b['room_name'],
                                                'roomCode' => $b['room_code'] ?? '',
                                                'roomLocation' => $b['location'] ?? '',
                                                'date' => format_date($b['date']),
                                                'time' => format_time($b['start_time']) . ' - ' . format_time($b['end_time']) . ' WIB',
                                                'attendees' => $b['attendees_count'] . ' Peserta',
                                                'status' => $b['status']
                                            ])); ?>)"
                                            class="text-brand-600 hover:text-brand-700 dark:text-brand-400 font-bold text-[10px] mt-1.5 inline-flex items-center gap-1 hover:underline"
                                        >
                                            <i class="fas fa-align-left text-[9px]"></i> Baca Selengkapnya
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($isPending): ?>
                                <div class="text-xs text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 p-2.5 rounded-xl border border-amber-200 dark:border-amber-900 flex items-center gap-2 mt-1">
                                    <i class="fas fa-info-circle text-amber-500"></i>
                                    <span>Pengajuan sedang menunggu verifikasi Administrator sebelum jadwal aktif di display ruangan.</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div class="flex sm:flex-row lg:flex-col gap-2 items-stretch lg:items-end justify-end border-t lg:border-t-0 pt-3 lg:pt-0 shrink-0">
                            <?php if ($isPending || $isConfirmed): ?>
                                <form method="POST" action="my_bookings.php" class="w-full sm:w-auto" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pemesanan ini?')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" class="w-full px-3.5 py-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-rose-200 dark:border-rose-900 rounded-xl font-bold transition text-xs flex items-center justify-center gap-1.5 shadow-sm">
                                        <i class="fas fa-ban"></i> Batalkan Pemesanan
                                    </button>
                                </form>
                            <?php elseif (!$isConfirmed): ?>
                                <a href="booking.php?room_id=<?php echo $b['room_id']; ?>" class="w-full sm:w-auto px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl font-bold transition text-xs flex items-center justify-center gap-1.5">
                                    <i class="fas fa-redo"></i> Pesan Lagi
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Baca Selengkapnya / Detail Meeting Modal -->
<div id="detailModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-700 animate-in fade-in zoom-in-95 duration-150 space-y-4">
        <div class="flex items-start justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300">
                    Rincian Agenda Pertemuan
                </span>
                <h3 id="modalTitle" class="text-base font-bold text-slate-900 dark:text-white mt-1"></h3>
            </div>
            <button onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="space-y-3.5 text-xs text-slate-600 dark:text-slate-300">
            <!-- Catatan Pertemuan Lengkap -->
            <div class="bg-slate-50 dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Catatan / Deskripsi Rapat:</div>
                <div id="modalPurpose" class="text-xs text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-wrap font-medium"></div>
            </div>

            <!-- Grid Details -->
            <div class="grid grid-cols-2 gap-2.5">
                <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] text-slate-400 block font-medium">Ruang Rapat:</span>
                    <span id="modalRoom" class="font-bold text-slate-800 dark:text-slate-200"></span>
                    <span id="modalLocation" class="text-[10px] text-slate-500 block"></span>
                </div>
                <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] text-slate-400 block font-medium">Kapasitas Peserta:</span>
                    <span id="modalAttendees" class="font-bold text-slate-800 dark:text-slate-200"></span>
                </div>
            </div>

            <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 block font-medium">Waktu & Jadwal:</span>
                <span id="modalSchedule" class="font-bold text-slate-800 dark:text-slate-200"></span>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex justify-end">
            <button onclick="closeDetailModal()" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold rounded-xl text-xs transition">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    const rawMyBookingsList = <?php echo json_encode(array_map(function($b) {
        $purpose = $b['purpose'] ?: 'Tidak ada catatan agenda tambahan.';
        return [
            'id' => (int)$b['id'],
            'room_id' => (int)$b['room_id'],
            'title' => $b['title'],
            'room_name' => $b['room_name'],
            'room_code' => $b['room_code'] ?? '',
            'location' => $b['location'] ?? '',
            'date' => $b['date'],
            'formatted_date' => format_date($b['date']),
            'start_time' => $b['start_time'],
            'end_time' => $b['end_time'],
            'formatted_time' => format_time($b['start_time']) . ' - ' . format_time($b['end_time']),
            'attendees_count' => (int)$b['attendees_count'],
            'purpose' => $purpose,
            'status' => $b['status']
        ];
    }, $my_bookings)); ?>;

    const csrfHiddenField = '<?php echo addslashes(csrf_field()); ?>';
    const myBookingSearchInput = document.getElementById('myBookingSearchInput');
    const clearMyBookingSearchBtn = document.getElementById('clearMyBookingSearchBtn');
    const myBookingSuggestionsBox = document.getElementById('myBookingSuggestionsBox');
    const myBookingStatusFilter = document.getElementById('myBookingStatusFilter');
    const myBookingsCount = document.getElementById('myBookingsCount');
    const myBookingsContainer = document.getElementById('myBookingsContainer');

    function openDetailModal(data) {
        document.getElementById('modalTitle').textContent = data.title;
        document.getElementById('modalPurpose').textContent = data.purpose;
        document.getElementById('modalRoom').textContent = data.roomName + (data.roomCode ? ' [' + data.roomCode + ']' : '');
        document.getElementById('modalLocation').textContent = data.roomLocation;
        document.getElementById('modalAttendees').textContent = data.attendees;
        document.getElementById('modalSchedule').textContent = data.date + ' • ' + data.time;
        
        const modal = document.getElementById('detailModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDetailModal() {
        const modal = document.getElementById('detailModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    function highlightText(text, query) {
        if (!query || !text) return escapeHtml(text);
        const safeText = escapeHtml(text);
        const safeQuery = escapeHtml(query).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${safeQuery})`, 'gi');
        return safeText.replace(regex, `<mark class="bg-amber-200 dark:bg-amber-800 text-slate-900 dark:text-white rounded px-0.5 font-bold">$1</mark>`);
    }

    function clearMyBookingSearch() {
        myBookingSearchInput.value = '';
        clearMyBookingSearchBtn.classList.add('hidden');
        myBookingSuggestionsBox.classList.add('hidden');
        applyLiveMyBookingFilter();
        myBookingSearchInput.focus();
    }

    function selectMyBookingSuggestion(value) {
        myBookingSearchInput.value = value;
        myBookingSuggestionsBox.classList.add('hidden');
        clearMyBookingSearchBtn.classList.remove('hidden');
        applyLiveMyBookingFilter();
    }

    function updateMyBookingSuggestions(query) {
        if (!query || query.length < 1) {
            myBookingSuggestionsBox.innerHTML = '';
            myBookingSuggestionsBox.classList.add('hidden');
            return;
        }

        const q = query.toLowerCase();
        const suggestions = [];
        const seen = new Set();

        rawMyBookingsList.forEach(b => {
            if (b.title && b.title.toLowerCase().includes(q) && !seen.has('title:' + b.title)) {
                seen.add('title:' + b.title);
                suggestions.push({ type: 'Agenda', text: b.title, icon: 'fa-calendar-alt text-brand-500' });
            }
            if (b.room_name && b.room_name.toLowerCase().includes(q) && !seen.has('room:' + b.room_name)) {
                seen.add('room:' + b.room_name);
                suggestions.push({ type: 'Ruangan', text: b.room_name, icon: 'fa-door-open text-amber-500' });
            }
            if (b.purpose && b.purpose.toLowerCase().includes(q) && !seen.has('purpose:' + b.purpose)) {
                seen.add('purpose:' + b.purpose);
                suggestions.push({ type: 'Catatan', text: b.purpose.substring(0, 45), icon: 'fa-file-alt text-blue-500' });
            }
        });

        if (suggestions.length === 0) {
            myBookingSuggestionsBox.innerHTML = `
                <div class="px-3.5 py-2 text-[11px] text-slate-400 text-center">
                    Tidak ditemukan saran untuk "<strong>${escapeHtml(query)}</strong>"
                </div>
            `;
            myBookingSuggestionsBox.classList.remove('hidden');
            return;
        }

        const topSuggestions = suggestions.slice(0, 6);
        let html = `
            <div class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-700/80 mb-1 flex items-center justify-between">
                <span>Rekomendasi Pemesanan</span>
                <span class="text-[9px] font-normal text-slate-400">Klik untuk memilih</span>
            </div>
        `;

        topSuggestions.forEach(item => {
            const escapedVal = escapeHtml(item.text);
            const highlightedVal = highlightText(item.text, query);
            html += `
                <button 
                    type="button" 
                    onmousedown="selectMyBookingSuggestion('${escapedVal.replace(/'/g, "\\'")}')" 
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

        myBookingSuggestionsBox.innerHTML = html;
        myBookingSuggestionsBox.classList.remove('hidden');
    }

    function applyLiveMyBookingFilter() {
        const query = myBookingSearchInput.value.trim();
        const q = query.toLowerCase();
        const selectedStatus = myBookingStatusFilter.value;

        if (query) {
            clearMyBookingSearchBtn.classList.remove('hidden');
        } else {
            clearMyBookingSearchBtn.classList.add('hidden');
        }

        let filtered = rawMyBookingsList.filter(b => {
            if (selectedStatus && b.status !== selectedStatus) return false;
            if (!q) return true;

            const title = (b.title || '').toLowerCase();
            const roomName = (b.room_name || '').toLowerCase();
            const roomCode = (b.room_code || '').toLowerCase();
            const location = (b.location || '').toLowerCase();
            const purpose = (b.purpose || '').toLowerCase();
            const formattedDate = (b.formatted_date || '').toLowerCase();

            return title.includes(q) || roomName.includes(q) || roomCode.includes(q) || location.includes(q) || purpose.includes(q) || formattedDate.includes(q);
        });

        // Priority Sorting: Pending first, then keyword relevance (title matches float to top)
        filtered.sort((a, b) => {
            // Keep pending on top
            if (a.status === 'pending' && b.status !== 'pending') return -1;
            if (a.status !== 'pending' && b.status === 'pending') return 1;

            if (q) {
                const aTitle = (a.title || '').toLowerCase();
                const bTitle = (b.title || '').toLowerCase();
                let aScore = 0;
                let bScore = 0;

                if (aTitle.startsWith(q)) aScore += 100;
                else if (aTitle.includes(q)) aScore += 75;
                if ((a.room_name || '').toLowerCase().includes(q)) aScore += 40;

                if (bTitle.startsWith(q)) bScore += 100;
                else if (bTitle.includes(q)) bScore += 75;
                if ((b.room_name || '').toLowerCase().includes(q)) bScore += 40;

                if (bScore !== aScore) return bScore - aScore;
            }

            return b.id - a.id;
        });

        if (myBookingsCount) {
            myBookingsCount.textContent = filtered.length;
        }

        renderMyBookingsList(filtered, query);
    }

    function renderMyBookingsList(bookings, highlightQuery = '') {
        if (!bookings || bookings.length === 0) {
            myBookingsContainer.innerHTML = `
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 p-12 text-center shadow-sm">
                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700/60 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Tidak Ada Pemesanan Ditemukan</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1 mb-4">Tidak ada riwayat pemesanan yang cocok dengan kriteria pencarian.</p>
                </div>
            `;
            return;
        }

        let html = '';
        bookings.forEach(b => {
            const isConfirmed = (b.status === 'confirmed');
            const isPending = (b.status === 'pending');
            const isCompleted = (b.status === 'completed');
            const isCancelled = (b.status === 'cancelled');

            const borderColor = isPending ? 'border-l-amber-500' : (isConfirmed ? 'border-l-emerald-500' : (isCompleted ? 'border-l-blue-500' : 'border-l-rose-500'));
            const bgClass = isPending ? 'bg-amber-50/20 dark:bg-amber-950/10' : '';

            let badgeHtml = '';
            if (isPending) {
                badgeHtml = `
                    <span class="px-2.5 py-1 bg-amber-100 dark:bg-amber-950/60 text-amber-900 dark:text-amber-300 border border-amber-300 dark:border-amber-700 rounded-lg font-bold text-[10px] uppercase flex items-center gap-1 animate-pulse shadow-sm">
                        <i class="fas fa-hourglass-half text-amber-600"></i> Menunggu Persetujuan Admin
                    </span>
                `;
            } else if (isConfirmed) {
                badgeHtml = `
                    <span class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-lg font-bold text-[10px] uppercase flex items-center gap-1">
                        <i class="fas fa-check-circle text-emerald-600"></i> Disetujui (Confirmed)
                    </span>
                `;
            } else if (isCompleted) {
                badgeHtml = `
                    <span class="px-2.5 py-1 bg-blue-50 dark:bg-blue-950/60 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-lg font-bold text-[10px] uppercase flex items-center gap-1">
                        <i class="fas fa-check-double text-blue-600"></i> Selesai
                    </span>
                `;
            } else {
                badgeHtml = `
                    <span class="px-2.5 py-1 bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-lg font-bold text-[10px] uppercase flex items-center gap-1">
                        <i class="fas fa-times-circle text-rose-600"></i> Dibatalkan / Ditolak
                    </span>
                `;
            }

            const displayTitle = highlightText(b.title, highlightQuery);
            const displayRoom = highlightText(b.room_name, highlightQuery);
            const displayPurpose = highlightText(b.purpose, highlightQuery);

            const isLong = b.purpose.length > 60;

            const modalPayload = escapeHtml(JSON.stringify({
                title: b.title,
                purpose: b.purpose,
                roomName: b.room_name,
                roomCode: b.room_code,
                roomLocation: b.location,
                date: b.formatted_date,
                time: b.formatted_time + ' WIB',
                attendees: b.attendees_count + ' Peserta',
                status: b.status
            }));

            let actionHtml = '';
            if (isPending || isConfirmed) {
                actionHtml = `
                    ${actionHtml}
                    <form method="POST" action="my_bookings.php" class="w-full sm:w-auto" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pemesanan ini?')">
                        ${csrfHiddenField}
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="booking_id" value="${b.id}">
                        <button type="submit" class="w-full px-3.5 py-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-rose-200 dark:border-rose-900 rounded-xl font-bold transition text-xs flex items-center justify-center gap-1.5 shadow-sm">
                            <i class="fas fa-ban"></i> Batalkan Pemesanan
                        </button>
                    </form>
                `;
            } else if (!isConfirmed) {
                actionHtml += `
                    <a href="booking.php?room_id=${b.room_id}" class="w-full sm:w-auto px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl font-bold transition text-xs flex items-center justify-center gap-1.5">
                        <i class="fas fa-redo"></i> Pesan Lagi
                    </a>
                `;
            }

            html += `
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 border-l-4 ${borderColor} shadow-sm p-4 sm:p-5 hover:shadow-md transition ${bgClass}">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="space-y-2 flex-grow">
                            <div class="flex flex-wrap items-center gap-2">
                                ${badgeHtml}
                                <span class="text-[11px] text-slate-400 font-mono">ID: #${b.id}</span>
                            </div>

                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">${displayTitle}</h3>
                                <div class="text-xs text-brand-600 dark:text-brand-400 font-semibold flex items-center gap-1 mt-0.5">
                                    <i class="fas fa-door-open"></i> ${displayRoom}
                                    <span class="text-slate-400 font-mono font-normal">(${escapeHtml(b.room_code)} - ${escapeHtml(b.location)})</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <div class="px-2.5 py-1 bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-100 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                                    <i class="far fa-calendar text-brand-500"></i>
                                    <span>${escapeHtml(b.formatted_date)}</span>
                                </div>
                                <div class="px-2.5 py-1 bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-100 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 flex items-center gap-1.5 font-mono">
                                    <i class="far fa-clock text-brand-500"></i>
                                    <span>${escapeHtml(b.formatted_time)}</span>
                                </div>
                                <div class="px-2.5 py-1 bg-slate-50 dark:bg-slate-700/50 rounded-lg border border-slate-100 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                                    <i class="fas fa-users text-brand-500"></i>
                                    <span>${b.attendees_count} Peserta</span>
                                </div>
                            </div>

                            ${b.purpose ? `
                                <div class="text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/60 p-3 rounded-xl border border-slate-200/80 dark:border-slate-700/80 mt-2">
                                    <div class="font-semibold text-[10px] uppercase text-slate-400 tracking-wider mb-0.5">Catatan Agenda:</div>
                                    <div class="${isLong ? 'line-clamp-2' : ''}">
                                        ${displayPurpose}
                                    </div>
                                    ${isLong ? `
                                        <button 
                                            type="button" 
                                            onclick="openDetailModal(${modalPayload})"
                                            class="text-brand-600 hover:text-brand-700 dark:text-brand-400 font-bold text-[10px] mt-1.5 inline-flex items-center gap-1 hover:underline"
                                        >
                                            <i class="fas fa-align-left text-[9px]"></i> Baca Selengkapnya
                                        </button>
                                    ` : ''}
                                </div>
                            ` : ''}

                            ${isPending ? `
                                <div class="text-xs text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 p-2.5 rounded-xl border border-amber-200 dark:border-amber-900 flex items-center gap-2 mt-1">
                                    <i class="fas fa-info-circle text-amber-500"></i>
                                    <span>Pengajuan sedang menunggu verifikasi Administrator sebelum jadwal aktif di display ruangan.</span>
                                </div>
                            ` : ''}
                        </div>

                        <div class="flex sm:flex-row lg:flex-col gap-2 items-stretch lg:items-end justify-end border-t lg:border-t-0 pt-3 lg:pt-0 shrink-0">
                            ${actionHtml}
                        </div>
                    </div>
                </div>
            `;
        });

        myBookingsContainer.innerHTML = html;
    }

    document.addEventListener('DOMContentLoaded', () => {
        myBookingSearchInput.addEventListener('input', () => {
            const q = myBookingSearchInput.value.trim();
            updateMyBookingSuggestions(q);
            applyLiveMyBookingFilter();
        });

        myBookingSearchInput.addEventListener('focus', () => {
            const q = myBookingSearchInput.value.trim();
            if (q) updateMyBookingSuggestions(q);
        });

        myBookingStatusFilter.addEventListener('change', applyLiveMyBookingFilter);

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#myBookingSearchContainer')) {
                myBookingSuggestionsBox.classList.add('hidden');
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
