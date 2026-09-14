<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            <i class="fas fa-tasks text-amber-500"></i> Kelola & Persetujuan Pemesanan
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Pengajuan yang baru masuk diurutkan paling atas. Tinjau agenda secara lengkap dan setujui/tolak jadwal.
        </p>
    </div>
    
    <!-- Real-time Live Status Badge & Indicator -->
    <div class="flex items-center gap-2.5">
        <div id="liveSyncBadge" class="flex items-center gap-2 px-3.5 py-2 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs font-semibold shadow-sm transition">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            <span id="liveStatusText">Live Sync Aktif</span>
        </div>
        
        <div id="pendingBadgeCount" class="<?php echo ($pending_count = count(array_filter($bookings, function($b) { return $b['status'] === 'pending'; }))) > 0 ? 'flex' : 'hidden'; ?> items-center gap-1.5 px-3.5 py-2 bg-amber-100 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700 rounded-xl text-xs font-bold shadow-sm">
            <i class="fas fa-bell text-amber-600 dark:text-amber-400 animate-bounce"></i>
            <span id="pendingCountText"><?php echo $pending_count; ?> Menunggu Approval</span>
        </div>
    </div>
</div>

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
        </select>
    </div>
    <button type="button" onclick="applyLiveFilter()" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-sm shrink-0">
        <i class="fas fa-search text-xs"></i>
        <span>Cari</span>
    </button>
</form>

<!-- Table Container -->
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm overflow-hidden mb-8">
    <div class="overflow-x-auto">
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
                            'status' => $b['status']
                        ];
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
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-700 animate-pulse shadow-sm">
                                    <i class="fas fa-hourglass-half text-amber-600"></i> Menunggu
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">
                                    <i class="fas fa-times-circle text-rose-600"></i> Ditolak
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- 6. Detail -->
                        <td class="py-3.5 px-4 text-center whitespace-nowrap overflow-hidden">
                            <button 
                                type="button" 
                                onclick="openDetailModal(<?php echo htmlspecialchars(json_encode($modalPayload)); ?>)"
                                class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold rounded-lg transition text-xs inline-flex items-center gap-1 shadow-sm"
                                title="Lihat Detail Rapat"
                            >
                                <i class="fas fa-eye text-brand-600 dark:text-brand-400"></i> Detail
                            </button>
                        </td>

                        <!-- 7. Aksi -->
                        <td class="py-3.5 px-4 text-center whitespace-nowrap overflow-hidden">
                            <div class="flex items-center justify-center gap-1.5">
                                <?php if ($b['status'] === 'pending'): ?>
                                    <!-- Aksi TERIMA -->
                                    <form method="POST" action="admin_bookings.php" class="inline-block">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit" class="p-1.5 px-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition text-xs shadow flex items-center gap-1" title="Setujui Rapat">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>

                                    <!-- Aksi TOLAK -->
                                    <form method="POST" action="admin_bookings.php" class="inline-block" onsubmit="return confirm('Tolak dan hapus pengajuan jadwal ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <button type="submit" class="p-1.5 px-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold rounded-lg transition text-xs flex items-center gap-1" title="Tolak Rapat">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <!-- Hapus Booking Terkonfirmasi / Selesai -->
                                    <form method="POST" action="admin_bookings.php" class="inline-block" onsubmit="return confirm('Hapus permanen data pemesanan ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <button type="submit" class="p-1.5 px-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition" title="Hapus Data">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Live Notification Toast Container -->
<div id="liveToastContainer" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none"></div>

<!-- Modal Detail Agenda Pertemuan -->
<div id="detailModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-700 animate-in fade-in zoom-in-95 duration-150 space-y-4">
        <div class="flex items-start justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300">
                    Rincian Agenda Pertemuan
                </span>
                <h3 id="modalTitle" class="text-base font-bold text-slate-900 dark:text-white mt-1 break-words"></h3>
            </div>
            <button onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="space-y-3 text-xs text-slate-600 dark:text-slate-300">
            <div class="bg-slate-50 dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Catatan / Deskripsi Rapat:</div>
                <div id="modalPurpose" class="text-xs text-slate-800 dark:text-slate-200 leading-relaxed break-words break-all whitespace-pre-wrap font-medium"></div>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] text-slate-400 block font-medium">Pemesan (PIC):</span>
                    <span id="modalUser" class="font-bold text-slate-800 dark:text-slate-200 block"></span>
                    <span id="modalDept" class="text-[10px] text-brand-600 dark:text-brand-400 block font-semibold"></span>
                </div>
                <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] text-slate-400 block font-medium">Ruang Rapat:</span>
                    <span id="modalRoom" class="font-bold text-slate-800 dark:text-slate-200 block"></span>
                    <span id="modalAttendees" class="text-[10px] text-slate-500 block"></span>
                </div>
            </div>

            <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 block font-medium">Waktu & Jadwal:</span>
                <span id="modalSchedule" class="font-bold text-slate-800 dark:text-slate-200 block"></span>
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
    // In-memory latest bookings store
    let rawBookingsList = <?php echo json_encode(array_map(function($b) {
        return [
            'id' => (int)$b['id'],
            'title' => $b['title'],
            'purpose' => $b['purpose'] ?: 'Tanpa catatan tambahan.',
            'user_name' => $b['user_name'],
            'user_dept' => $b['user_dept'] ?? 'Internal',
            'userEmail' => $b['user_email'] ?? '-',
            'room_id' => (int)$b['room_id'],
            'room_name' => $b['room_name'],
            'room_code' => $b['room_code'] ?? '',
            'date' => $b['date'],
            'formatted_date' => format_date($b['date']),
            'start_time' => substr($b['start_time'], 0, 5),
            'end_time' => substr($b['end_time'], 0, 5),
            'attendees_count' => (int)$b['attendees_count'],
            'status' => $b['status']
        ];
    }, $bookings)); ?>;

    let currentHash = '';
    let previousPendingCount = <?php echo $pending_count ?? 0; ?>;

    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const suggestionsBox = document.getElementById('searchSuggestionsBox');
    const statusSelect = document.getElementById('statusSelect');

    function openDetailModal(data) {
        document.getElementById('modalTitle').textContent = data.title;
        document.getElementById('modalPurpose').textContent = data.purpose;
        document.getElementById('modalUser').textContent = data.userName;
        document.getElementById('modalDept').textContent = data.userDept + ' (' + data.userEmail + ')';
        document.getElementById('modalRoom').textContent = data.roomName + (data.roomCode ? ' [' + data.roomCode + ']' : '');
        document.getElementById('modalAttendees').textContent = 'Kapasitas: ' + data.attendees;
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

    function showToast(message, type = 'info') {
        const container = document.getElementById('liveToastContainer');
        const toast = document.createElement('div');
        toast.className = 'pointer-events-auto flex items-center gap-3 px-4 py-3 bg-slate-900/95 text-white dark:bg-white dark:text-slate-900 rounded-2xl shadow-2xl border border-slate-700 text-xs font-semibold transform translate-y-2 opacity-0 transition-all duration-300';
        
        let iconHtml = '<i class="fas fa-bell text-amber-400"></i>';
        if (type === 'success') iconHtml = '<i class="fas fa-check-circle text-emerald-400"></i>';

        toast.innerHTML = `
            ${iconHtml}
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" class="ml-2 text-slate-400 hover:text-white p-1">
                <i class="fas fa-times text-xs"></i>
            </button>
        `;
        
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.remove('translate-y-2', 'opacity-0');
        }, 10);

        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
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

    function clearSearchInput() {
        searchInput.value = '';
        clearSearchBtn.classList.add('hidden');
        suggestionsBox.classList.add('hidden');
        applyLiveFilter();
        searchInput.focus();
    }

    function selectSuggestion(value) {
        searchInput.value = value;
        suggestionsBox.classList.add('hidden');
        clearSearchBtn.classList.remove('hidden');
        applyLiveFilter();
    }

    function updateAutocompleteSuggestions(query) {
        if (!query || query.length < 1) {
            suggestionsBox.innerHTML = '';
            suggestionsBox.classList.add('hidden');
            return;
        }

        const q = query.toLowerCase();
        const suggestions = [];
        const seen = new Set();

        rawBookingsList.forEach(b => {
            // Check title
            if (b.title && b.title.toLowerCase().includes(q) && !seen.has('title:' + b.title)) {
                seen.add('title:' + b.title);
                suggestions.push({ type: 'Agenda', text: b.title, icon: 'fa-calendar-alt text-amber-500' });
            }
            // Check user name
            if (b.user_name && b.user_name.toLowerCase().includes(q) && !seen.has('user:' + b.user_name)) {
                seen.add('user:' + b.user_name);
                suggestions.push({ type: 'Pemesan', text: b.user_name, icon: 'fa-user text-blue-500' });
            }
            // Check dept
            if (b.user_dept && b.user_dept.toLowerCase().includes(q) && !seen.has('dept:' + b.user_dept)) {
                seen.add('dept:' + b.user_dept);
                suggestions.push({ type: 'Divisi', text: b.user_dept, icon: 'fa-building text-indigo-500' });
            }
            // Check room
            if (b.room_name && b.room_name.toLowerCase().includes(q) && !seen.has('room:' + b.room_name)) {
                seen.add('room:' + b.room_name);
                suggestions.push({ type: 'Ruangan', text: b.room_name, icon: 'fa-door-open text-emerald-500' });
            }
        });

        if (suggestions.length === 0) {
            suggestionsBox.innerHTML = `
                <div class="px-3.5 py-2 text-[11px] text-slate-400 text-center">
                    Tidak ditemukan rekomendasi untuk "<strong>${escapeHtml(query)}</strong>"
                </div>
            `;
            suggestionsBox.classList.remove('hidden');
            return;
        }

        // Limit top 6 suggestions
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
                    onmousedown="selectSuggestion('${escapedVal.replace(/'/g, "\\'")}')" 
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

        suggestionsBox.innerHTML = html;
        suggestionsBox.classList.remove('hidden');
    }

    function applyLiveFilter() {
        const query = searchInput.value.trim();
        const q = query.toLowerCase();
        const statusFilter = statusSelect.value;

        if (query) {
            clearSearchBtn.classList.remove('hidden');
        } else {
            clearSearchBtn.classList.add('hidden');
        }

        let filtered = rawBookingsList.filter(b => {
            if (statusFilter && b.status !== statusFilter) {
                return false;
            }
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
        // Score priority: Title match > Purpose match > PIC match > Room match > Others
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
                    return bScore - aScore; // Highest score at the top
                }

                // If same score, pending first
                const aPending = a.status === 'pending' ? 1 : 0;
                const bPending = b.status === 'pending' ? 1 : 0;
                if (bPending !== aPending) return bPending - aPending;

                return b.id - a.id;
            });
        } else {
            // Default sort: pending first, then by ID descending
            filtered.sort((a, b) => {
                const aPending = a.status === 'pending' ? 1 : 0;
                const bPending = b.status === 'pending' ? 1 : 0;
                if (bPending !== aPending) return bPending - aPending;
                return b.id - a.id;
            });
        }

        renderBookingsTable(filtered, query);
    }

    function renderBookingsTable(bookings, highlightQuery = '') {
        const tbody = document.getElementById('bookingsTableBody');
        if (!bookings || bookings.length === 0) {
            tbody.innerHTML = `
                <tr id="emptyRow">
                    <td colspan="7" class="py-12 text-center text-slate-400">
                        <i class="fas fa-inbox text-3xl mb-2 block"></i>
                        Tidak ada data booking yang sesuai dengan kriteria pencarian.
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        bookings.forEach(b => {
            const isPending = (b.status === 'pending');
            const purposeText = b.purpose || 'Tanpa catatan tambahan.';
            
            const modalData = {
                title: b.title,
                purpose: purposeText,
                userName: b.user_name,
                userDept: b.user_dept || 'Internal',
                userEmail: b.userEmail || b.user_email || '-',
                roomName: b.room_name,
                roomCode: b.room_code || '',
                date: b.formatted_date,
                time: b.start_time + ' - ' + b.end_time + ' WIB',
                attendees: b.attendees_count + ' Orang',
                status: b.status
            };

            const encodedData = escapeHtml(JSON.stringify(modalData));

            const displayTitle = highlightText(b.title, highlightQuery);
            const displayPurpose = highlightText(purposeText, highlightQuery);
            const displayUser = highlightText(b.user_name, highlightQuery);
            const displayDept = highlightText(b.user_dept || 'Divisi Terkait', highlightQuery);
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
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-700 animate-pulse shadow-sm">
                        <i class="fas fa-hourglass-half text-amber-600"></i> Menunggu
                    </span>
                `;
            } else {
                statusHtml = `
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">
                        <i class="fas fa-times-circle text-rose-600"></i> Ditolak
                    </span>
                `;
            }

            let actionHtml = '';
            if (isPending) {
                actionHtml = `
                    <div class="flex items-center justify-center gap-1.5">
                        <form method="POST" action="admin_bookings.php" class="inline-block">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="booking_id" value="${b.id}">
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit" class="p-1.5 px-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition text-xs shadow flex items-center gap-1" title="Setujui Rapat">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        <form method="POST" action="admin_bookings.php" class="inline-block" onsubmit="return confirm('Tolak dan hapus pengajuan jadwal ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="booking_id" value="${b.id}">
                            <button type="submit" class="p-1.5 px-2.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold rounded-lg transition text-xs flex items-center gap-1" title="Tolak Rapat">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                `;
            } else {
                actionHtml = `
                    <div class="flex items-center justify-center gap-1.5">
                        <form method="POST" action="admin_bookings.php" class="inline-block" onsubmit="return confirm('Hapus permanen data pemesanan ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="booking_id" value="${b.id}">
                            <button type="submit" class="p-1.5 px-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition" title="Hapus Data">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </div>
                `;
            }

            html += `
                <tr id="booking-row-${b.id}" class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition ${isPending ? 'bg-amber-50/50 dark:bg-amber-950/25 border-l-4 border-amber-500' : ''}">
                    <!-- 1. Agenda -->
                    <td class="py-3.5 px-4 overflow-hidden">
                        <div class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm truncate" title="${escapeHtml(b.title)}">
                            ${displayTitle}
                        </div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5" title="${escapeHtml(purposeText)}">
                            ${displayPurpose}
                        </div>
                        <div class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                            <i class="fas fa-users text-slate-400"></i>
                            <span>Peserta: ${b.attendees_count} Orang</span>
                        </div>
                    </td>

                    <!-- 2. Pemesan & Divisi -->
                    <td class="py-3.5 px-4 overflow-hidden">
                        <div class="font-bold text-slate-800 dark:text-slate-200 text-xs truncate flex items-center gap-1.5" title="${escapeHtml(b.user_name)}">
                            <i class="fas fa-user-circle text-slate-400 shrink-0"></i>
                            <span class="truncate">${displayUser}</span>
                        </div>
                        <div class="text-[11px] text-brand-600 dark:text-brand-400 font-semibold truncate mt-0.5" title="${escapeHtml(b.user_dept || 'Divisi Terkait')}">
                            ${displayDept}
                        </div>
                        <div class="text-[10px] text-slate-400 font-mono truncate mt-0.5" title="${escapeHtml(b.userEmail || b.user_email || '')}">
                            ${escapeHtml(b.userEmail || b.user_email || '')}
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
                            onclick="openDetailModal(${encodedData})"
                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold rounded-lg transition text-xs inline-flex items-center gap-1 shadow-sm"
                            title="Lihat Detail Rapat"
                        >
                            <i class="fas fa-eye text-brand-600 dark:text-brand-400"></i> Detail
                        </button>
                    </td>

                    <!-- 7. Aksi -->
                    <td class="py-3.5 px-4 text-center whitespace-nowrap overflow-hidden">
                        ${actionHtml}
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    async function syncAdminBookings() {
        try {
            const url = `api/admin_bookings_live.php`;
            const response = await fetch(url);
            if (!response.ok) return;

            const data = await response.json();
            if (!data.success) return;

            // Update pending badge UI
            const pendingBadge = document.getElementById('pendingBadgeCount');
            const pendingText = document.getElementById('pendingCountText');
            if (data.pending_count > 0) {
                pendingText.textContent = `${data.pending_count} Menunggu Approval`;
                pendingBadge.classList.remove('hidden');
                pendingBadge.classList.add('flex');
            } else {
                pendingBadge.classList.add('hidden');
                pendingBadge.classList.remove('flex');
            }

            // Check if there are changes
            if (currentHash && data.hash !== currentHash) {
                rawBookingsList = data.bookings;
                applyLiveFilter();

                if (data.pending_count > previousPendingCount) {
                    showToast(`🔔 Ada ${data.pending_count - previousPendingCount} pengajuan pemesanan baru masuk!`, 'info');
                } else {
                    showToast(`Data pemesanan diperbarui otomatis.`, 'success');
                }
            } else if (!currentHash) {
                rawBookingsList = data.bookings;
            }

            currentHash = data.hash;
            previousPendingCount = data.pending_count;

        } catch (err) {
            console.error('Auto-sync error:', err);
        }
    }

    // Event Listeners for Live Search & Autocomplete
    document.addEventListener('DOMContentLoaded', () => {
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.trim();
            updateAutocompleteSuggestions(q);
            applyLiveFilter();
        });

        searchInput.addEventListener('focus', () => {
            const q = searchInput.value.trim();
            if (q) updateAutocompleteSuggestions(q);
        });

        statusSelect.addEventListener('change', () => {
            applyLiveFilter();
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#searchContainer')) {
                suggestionsBox.classList.add('hidden');
            }
        });

        syncAdminBookings();
        setInterval(syncAdminBookings, 4000);
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
