<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- Welcome Banner -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Dashboard Ruangan</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Kelola dan pesan ruang rapat perusahaan dengan cepat dan mudah.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <?php if (is_admin()): ?>
        <a href="display_lobby.php" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-bold rounded-xl border border-blue-200 dark:border-blue-700 transition text-sm">
            <i class="fas fa-desktop"></i> Monitor Lobby
        </a>
        <a href="display.php" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-50 dark:bg-amber-900/30 hover:bg-amber-100 dark:hover:bg-amber-900/50 text-amber-700 dark:text-amber-300 font-bold rounded-xl border border-amber-200 dark:border-amber-700 transition text-sm">
            <i class="fas fa-tv"></i> Monitor Pintu
        </a>
        <?php endif; ?>
        <button type="button" onclick="openBookingModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-md shadow-brand-500/20 transition text-sm">
            <i class="fas fa-calendar-plus"></i> Pesan Ruangan
        </button>
    </div>
</div>

<!-- Statistics Cards Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/80 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400">Total Ruangan</span>
            <div class="text-2xl font-extrabold text-slate-800 dark:text-white mt-1"><?php echo $total_rooms; ?></div>
        </div>
        <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/30 text-brand-600 dark:text-brand-400 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-door-open"></i>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/80 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400">Tersedia Siap Pakai</span>
            <div class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1"><?php echo $available_rooms; ?></div>
        </div>
        <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-check-circle"></i>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/80 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400">Jadwal Hari Ini</span>
            <div class="text-2xl font-extrabold text-brand-600 dark:text-brand-400 mt-1"><?php echo $active_bookings_today; ?></div>
        </div>
        <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-clock"></i>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-100 dark:border-slate-700/80 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400">Total Pengguna</span>
            <div class="text-2xl font-extrabold text-slate-800 dark:text-white mt-1"><?php echo $total_users; ?></div>
        </div>
        <div class="w-12 h-12 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-xl flex items-center justify-center text-xl">
            <i class="fas fa-users"></i>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main Section: Room Directory -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                <div class="flex items-center gap-2">
                    <i class="fas fa-list-ul text-brand-600 dark:text-brand-400 text-lg"></i>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Daftar Ruangan Rapat</h2>
                </div>
                <span id="roomCountBadge" class="text-xs font-semibold px-3 py-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-full border border-slate-200 dark:border-slate-600 w-fit">
                    <?php echo count($rooms); ?> Ruangan Ditemukan
                </span>
            </div>

            <!-- Instant Live Search & Filter Form -->
            <form id="roomFilterForm" onsubmit="return false;" class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-200/80 dark:border-slate-700 mb-6 grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-5 relative" id="roomSearchContainer">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                        <i class="fas fa-search"></i>
                    </div>
                    <input 
                        type="text" 
                        name="search" 
                        id="roomSearchInput" 
                        autocomplete="off" 
                        class="w-full pl-9 pr-8 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 transition" 
                        placeholder="Ketik untuk mencari (contoh: samudra, lantai 2, proyektor)..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                    <button 
                        type="button" 
                        id="clearRoomSearchBtn" 
                        onclick="clearRoomSearch()" 
                        class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hidden text-xs transition" 
                        title="Hapus pencarian"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <!-- Floating Suggestions Dropdown -->
                    <div id="roomSearchSuggestions" class="hidden absolute left-0 right-0 top-full mt-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl z-50 overflow-hidden max-h-72 overflow-y-auto py-1"></div>
                </div>

                <div class="sm:col-span-3">
                    <select name="status" id="roomStatusFilter" class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer">
                        <option value="">-- Semua Status --</option>
                        <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Tersedia</option>
                        <option value="occupied" <?php echo $status_filter === 'occupied' ? 'selected' : ''; ?>>Terpakai</option>
                        <option value="maintenance" <?php echo $status_filter === 'maintenance' ? 'selected' : ''; ?>>Perawatan</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <select name="min_capacity" id="roomCapacityFilter" class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer">
                        <option value="0">Kapasitas</option>
                        <option value="5" <?php echo $capacity_filter === 5 ? 'selected' : ''; ?>>>= 5 Orang</option>
                        <option value="10" <?php echo $capacity_filter === 10 ? 'selected' : ''; ?>>>= 10 Orang</option>
                        <option value="20" <?php echo $capacity_filter === 20 ? 'selected' : ''; ?>>>= 20 Orang</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <button type="button" onclick="applyLiveRoomFilter()" class="w-full py-2 px-3 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg text-xs transition flex items-center justify-center gap-1.5 shadow-sm">
                        <i class="fas fa-search"></i>
                        <span>Cari</span>
                    </button>
                </div>
            </form>

            <!-- Room Cards Container (Rendered dynamically on live search) -->
            <div id="roomCardsContainer" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <?php if (empty($rooms)): ?>
                    <div class="col-span-2 text-center py-12 text-slate-400">
                        <i class="fas fa-door-closed text-4xl mb-3 opacity-60"></i>
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Tidak ada ruangan yang sesuai dengan kriteria pencarian.</p>
                        <a href="dashboard.php" class="inline-block mt-3 px-4 py-2 text-xs font-semibold text-brand-600 dark:text-brand-400 border border-brand-200 dark:border-brand-800 rounded-lg hover:bg-brand-50 dark:hover:bg-brand-950/50 transition">Reset Filter</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <div class="bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden flex flex-col hover:border-brand-300 dark:hover:border-brand-500 transition duration-200">
                            <div class="relative h-44 bg-slate-100 dark:bg-slate-900 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($room['image'] ?: 'public/rooms/KalTim.jpeg'); ?>" onerror="this.onerror=null; this.src='public/rooms/KalTim.jpeg';" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($room['name']); ?>">
                                <div class="absolute top-3 right-3">
                                    <?php if ($room['status'] === 'available'): ?>
                                        <span class="px-2.5 py-1 bg-emerald-500 text-white text-xs font-bold rounded-lg shadow-md flex items-center gap-1">
                                            <i class="fas fa-check text-[10px]"></i> Tersedia
                                        </span>
                                    <?php elseif ($room['status'] === 'occupied'): ?>
                                        <span class="px-2.5 py-1 bg-amber-500 text-white text-xs font-bold rounded-lg shadow-md flex items-center gap-1">
                                            <i class="fas fa-clock text-[10px]"></i> Terpakai
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 bg-slate-600 text-white text-xs font-bold rounded-lg shadow-md flex items-center gap-1">
                                            <i class="fas fa-tools text-[10px]"></i> Perawatan
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="p-4 flex-1 flex flex-col">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <h3 class="font-bold text-slate-900 dark:text-white text-base leading-snug"><?php echo htmlspecialchars($room['name']); ?></h3>
                                    <span class="text-[11px] font-mono px-2 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded border border-slate-200 dark:border-slate-600 font-semibold"><?php echo htmlspecialchars($room['code']); ?></span>
                                </div>

                                <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 mb-2">
                                    <i class="fas fa-map-marker-alt text-rose-500"></i> <?php echo htmlspecialchars($room['location']); ?> (<?php echo htmlspecialchars($room['floor']); ?>)
                                </p>

                                <div class="text-xs text-slate-600 dark:text-slate-300 mb-2 flex items-center gap-1 font-medium">
                                    <i class="fas fa-users text-brand-600 dark:text-brand-400"></i> Kapasitas: <strong><?php echo $room['capacity']; ?> Orang</strong>
                                </div>

                                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mb-3">
                                    <?php echo htmlspecialchars($room['description']); ?>
                                </p>

                                <!-- Facilities Tags -->
                                <div class="flex flex-wrap gap-1 mb-4">
                                    <?php
                                    $facs = array_map('trim', explode(',', $room['facilities']));
                                    foreach (array_slice($facs, 0, 4) as $fac):
                                        if (!empty($fac)):
                                    ?>
                                        <span class="text-[10px] font-medium px-2 py-0.5 bg-slate-50 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 rounded-md flex items-center gap-1">
                                            <i class="fas fa-check text-emerald-500 text-[8px]"></i> <?php echo htmlspecialchars($fac); ?>
                                        </span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>

                                <div class="pt-3 border-t border-slate-100 dark:border-slate-700/80 mt-auto flex items-center justify-between gap-2">
                                    <button type="button" onclick="openBookingModal(<?php echo $room['id']; ?>)" class="flex-1 py-2 px-3 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg text-xs text-center transition shadow-sm <?php echo $room['status'] === 'maintenance' ? 'opacity-50 pointer-events-none' : ''; ?>">
                                         <i class="fas fa-calendar-check mr-1"></i> Pesan
                                     </button>
                                    <a href="calendar.php?room_id=<?php echo $room['id']; ?>" class="py-2 px-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-lg text-xs transition" title="Lihat Jadwal">
                                        <i class="fas fa-calendar-alt"></i> Jadwal
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar: Today's Schedule -->
    <div class="space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-700">
                <div class="flex items-center gap-2">
                    <i class="fas fa-calendar-day text-brand-600 dark:text-brand-400"></i>
                    <h3 class="font-bold text-slate-900 dark:text-white text-base">Jadwal Hari Ini</h3>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 bg-brand-50 dark:bg-brand-950/60 text-brand-600 dark:text-brand-400 rounded-lg border border-brand-200 dark:border-brand-800">
                    <?php echo date('d M Y'); ?>
                </span>
            </div>

            <?php if (empty($today_bookings)): ?>
                <div class="text-center py-8 text-slate-400">
                    <i class="fas fa-calendar-check text-3xl text-emerald-400 mb-2 opacity-80"></i>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Tidak ada agenda rapat terjadwal untuk hari ini.</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($today_bookings as $tb): ?>
                        <div class="p-3.5 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-200/80 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 transition">
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <span class="text-xs font-bold px-2 py-0.5 bg-brand-100 dark:bg-brand-900/40 text-brand-700 dark:text-brand-300 rounded-md flex items-center gap-1">
                                    <i class="fas fa-clock text-[10px]"></i> <?php echo format_time($tb['start_time']); ?> - <?php echo format_time($tb['end_time']); ?>
                                </span>
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded <?php echo $tb['status'] === 'confirmed' ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400' : 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400'; ?>">
                                    <?php echo ucfirst($tb['status']); ?>
                                </span>
                            </div>
                            <h4 class="font-bold text-slate-800 dark:text-slate-100 text-sm mb-1"><?php echo htmlspecialchars($tb['title']); ?></h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 mb-2">
                                <i class="fas fa-door-open text-slate-400"></i> <?php echo htmlspecialchars($tb['room_name']); ?>
                            </p>

                            <div class="flex items-center gap-2 pt-2 border-t border-slate-200/60 dark:border-slate-700/60">
                                <img src="<?php echo htmlspecialchars($tb['user_avatar'] ?: 'https://via.placeholder.com/30'); ?>" class="w-5 h-5 rounded-full object-cover">
                                <span class="text-xs text-slate-600 dark:text-slate-300 font-medium"><?php echo htmlspecialchars($tb['user_name']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700 text-center">
                <a href="calendar.php" class="inline-flex items-center justify-center w-full py-2 px-3 text-xs font-bold text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-950/50 hover:bg-brand-100 dark:hover:bg-brand-900/60 border border-brand-200 dark:border-brand-800 rounded-xl transition gap-1.5">
                    <i class="fas fa-calendar-week"></i> Lihat Kalender Lengkap
                </a>
            </div>
        </div>

        <?php if (is_admin()): ?>
            <?php require __DIR__ . '/_admin_sidebar.php'; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Popup Booking Ruangan -->
<div id="bookingModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div id="bookingModalCard" class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700/80 w-full max-w-4xl max-h-[92vh] overflow-hidden flex flex-col transform scale-95 transition-transform duration-300">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-700/80 bg-gradient-to-r from-sky-50/50 via-white to-sky-50/30 dark:from-slate-800 dark:to-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-brand-50 dark:bg-brand-950/60 text-brand-600 dark:text-brand-400 rounded-xl flex items-center justify-center text-lg font-bold">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Form Pemesanan Ruangan</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Isi formulir di bawah ini untuk memesan ruang rapat.</p>
                </div>
            </div>
            <button type="button" onclick="closeBookingModal()" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition" title="Tutup">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-6 sm:p-8 overflow-y-auto flex-1">
            <!-- Dynamic Error Alert inside Modal -->
            <div id="modalBookingError" class="hidden p-4 mb-6 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-300 text-sm flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-rose-500 text-lg mt-0.5"></i>
                <div id="modalBookingErrorMessage" class="flex-1 leading-relaxed"></div>
            </div>

            <form id="bookingModalForm" method="POST" action="booking.php" onsubmit="handleBookingSubmit(event)" class="space-y-6" data-booking-form>
                <?php echo csrf_field(); ?>
                <input type="hidden" name="is_ajax" value="1">

                <?php
                $bookingFormPrefix = 'modalBooking';
                $bookingFormRooms = !empty($active_rooms) ? $active_rooms : (!empty($rooms) ? $rooms : []);
                $bookingFormSelectedRoomId = 0;
                $bookingFormValues = [
                    'user_name' => $_SESSION['user_name'] ?? '',
                    'user_dept' => $_SESSION['department'] ?? '',
                    'title' => '',
                    'date' => date('Y-m-d'),
                    'start_time' => '09:00',
                    'end_time' => '10:00',
                    'activity_type' => 'internal_divisi',
                    'attendees_count' => 1,
                    'purpose' => ''
                ];
                require __DIR__ . '/../booking/_form_fields.php';
                ?>

                <!-- Modal Footer Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" onclick="closeBookingModal()" class="py-2.5 px-5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-xl text-sm transition">
                        Batal
                    </button>
                    <button type="submit" id="modalSubmitBtn" class="py-2.5 px-6 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-lg shadow-brand-500/20 transition text-sm flex items-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        <span>Konfirmasi Booking</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="public/js/booking-form.js"></script>
<script>
    // All rooms in-memory data for instant client-side filtering & re-ranking
    const rawDashboardRooms = <?php echo json_encode(array_map(function ($r) {
        return [
            'id' => (int)$r['id'],
            'name' => $r['name'] ?? '',
            'code' => $r['code'] ?? '',
            'capacity' => (int)($r['capacity'] ?? 0),
            'location' => $r['location'] ?? '',
            'floor' => $r['floor'] ?? '',
            'facilities' => $r['facilities'] ?? '',
            'description' => $r['description'] ?? '',
            'image' => $r['image'] ?: 'public/rooms/KalTim.jpeg',
            'status' => $r['status'] ?? 'available'
        ];
    }, $rooms), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

    const roomSearchInput = document.getElementById('roomSearchInput');
    const clearRoomSearchBtn = document.getElementById('clearRoomSearchBtn');
    const roomSuggestionsBox = document.getElementById('roomSearchSuggestions');
    const roomStatusFilter = document.getElementById('roomStatusFilter');
    const roomCapacityFilter = document.getElementById('roomCapacityFilter');
    const roomCardsContainer = document.getElementById('roomCardsContainer');
    const roomCountBadge = document.getElementById('roomCountBadge');

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

    function clearRoomSearch() {
        roomSearchInput.value = '';
        clearRoomSearchBtn.classList.add('hidden');
        roomSuggestionsBox.classList.add('hidden');
        applyLiveRoomFilter();
        roomSearchInput.focus();
    }

    function selectRoomSuggestion(value) {
        roomSearchInput.value = value;
        roomSuggestionsBox.classList.add('hidden');
        clearRoomSearchBtn.classList.remove('hidden');
        applyLiveRoomFilter();
    }

    function updateRoomSuggestions(query) {
        if (!query || query.length < 1) {
            roomSuggestionsBox.innerHTML = '';
            roomSuggestionsBox.classList.add('hidden');
            return;
        }

        const q = query.toLowerCase();
        const suggestions = [];
        const seen = new Set();

        rawDashboardRooms.forEach(room => {
            // Room Name
            if (room.name && room.name.toLowerCase().includes(q) && !seen.has('name:' + room.name)) {
                seen.add('name:' + room.name);
                suggestions.push({ type: 'Ruangan', text: room.name, icon: 'fa-door-open text-brand-600 dark:text-brand-400' });
            }
            // Room Code
            if (room.code && room.code.toLowerCase().includes(q) && !seen.has('code:' + room.code)) {
                seen.add('code:' + room.code);
                suggestions.push({ type: 'Kode', text: room.code, icon: 'fa-tag text-amber-500' });
            }
            // Location / Floor
            if (room.location && room.location.toLowerCase().includes(q) && !seen.has('loc:' + room.location)) {
                seen.add('loc:' + room.location);
                suggestions.push({ type: 'Lokasi', text: room.location, icon: 'fa-map-marker-alt text-rose-500' });
            }
            if (room.floor && room.floor.toLowerCase().includes(q) && !seen.has('floor:' + room.floor)) {
                seen.add('floor:' + room.floor);
                suggestions.push({ type: 'Lantai', text: room.floor, icon: 'fa-layer-group text-indigo-500' });
            }
            // Facilities
            if (room.facilities) {
                const facs = room.facilities.split(',').map(f => f.trim());
                facs.forEach(fac => {
                    if (fac.toLowerCase().includes(q) && !seen.has('fac:' + fac)) {
                        seen.add('fac:' + fac);
                        suggestions.push({ type: 'Fasilitas', text: fac, icon: 'fa-check-circle text-emerald-500' });
                    }
                });
            }
        });

        if (suggestions.length === 0) {
            roomSuggestionsBox.innerHTML = `
                <div class="px-3.5 py-2 text-[11px] text-slate-400 text-center">
                    Tidak ada saran untuk "<strong>${escapeHtml(query)}</strong>"
                </div>
            `;
            roomSuggestionsBox.classList.remove('hidden');
            return;
        }

        const topSuggestions = suggestions.slice(0, 6);
        let html = `
            <div class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-700/80 mb-1 flex items-center justify-between">
                <span>Rekomendasi Ruangan</span>
                <span class="text-[9px] font-normal text-slate-400">Klik untuk memilih</span>
            </div>
        `;

        topSuggestions.forEach(item => {
            const escapedVal = escapeHtml(item.text);
            const highlightedVal = highlightText(item.text, query);
            html += `
                <button 
                    type="button" 
                    onmousedown="selectRoomSuggestion('${escapedVal.replace(/'/g, "\\'")}')" 
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

        roomSuggestionsBox.innerHTML = html;
        roomSuggestionsBox.classList.remove('hidden');
    }

    function applyLiveRoomFilter() {
        const query = roomSearchInput.value.trim();
        const q = query.toLowerCase();
        const selectedStatus = roomStatusFilter.value;
        const minCapacity = parseInt(roomCapacityFilter.value, 10) || 0;

        if (query) {
            clearRoomSearchBtn.classList.remove('hidden');
        } else {
            clearRoomSearchBtn.classList.add('hidden');
        }

        let filtered = rawDashboardRooms.filter(room => {
            if (selectedStatus && room.status !== selectedStatus) return false;
            if (minCapacity > 0 && room.capacity < minCapacity) return false;
            if (!q) return true;

            const searchable = [
                room.name,
                room.code,
                room.location,
                room.floor,
                room.facilities,
                room.description
            ].join(' ').toLowerCase();

            return searchable.includes(q);
        });

        // Priority Re-ranking: Matches in Name/Code float to the TOP!
        if (q) {
            filtered.sort((a, b) => {
                const aName = (a.name || '').toLowerCase();
                const aCode = (a.code || '').toLowerCase();
                const bName = (b.name || '').toLowerCase();
                const bCode = (b.code || '').toLowerCase();

                let aScore = 0;
                let bScore = 0;

                if (aName.startsWith(q) || aCode.startsWith(q)) aScore += 100;
                else if (aName.includes(q) || aCode.includes(q)) aScore += 75;
                if ((a.location || '').toLowerCase().includes(q)) aScore += 40;
                if ((a.facilities || '').toLowerCase().includes(q)) aScore += 30;

                if (bName.startsWith(q) || bCode.startsWith(q)) bScore += 100;
                else if (bName.includes(q) || bCode.includes(q)) bScore += 75;
                if ((b.location || '').toLowerCase().includes(q)) bScore += 40;
                if ((b.facilities || '').toLowerCase().includes(q)) bScore += 30;

                if (bScore !== aScore) {
                    return bScore - aScore; // Highest score first
                }

                return a.id - b.id;
            });
        }

        if (roomCountBadge) {
            roomCountBadge.textContent = `${filtered.length} Ruangan Ditemukan`;
        }

        renderDashboardRooms(filtered, query);
    }

    function renderDashboardRooms(rooms, highlightQuery = '') {
        if (!rooms || rooms.length === 0) {
            roomCardsContainer.innerHTML = `
                <div class="col-span-2 text-center py-12 text-slate-400">
                    <i class="fas fa-door-closed text-4xl mb-3 opacity-60"></i>
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Tidak ada ruangan yang sesuai dengan kriteria pencarian.</p>
                    <button type="button" onclick="clearRoomSearch()" class="inline-block mt-3 px-4 py-2 text-xs font-semibold text-brand-600 dark:text-brand-400 border border-brand-200 dark:border-brand-800 rounded-lg hover:bg-brand-50 dark:hover:bg-brand-950/50 transition">
                        Reset Filter
                    </button>
                </div>
            `;
            return;
        }

        let html = '';
        rooms.forEach(room => {
            const displayName = highlightText(room.name, highlightQuery);
            const displayCode = highlightText(room.code, highlightQuery);
            const displayLocation = highlightText(room.location, highlightQuery);
            const displayFloor = highlightText(room.floor, highlightQuery);
            const displayDesc = highlightText(room.description, highlightQuery);

            let statusBadge = '';
            if (room.status === 'available') {
                statusBadge = `
                    <span class="px-2.5 py-1 bg-emerald-500 text-white text-xs font-bold rounded-lg shadow-md flex items-center gap-1">
                        <i class="fas fa-check text-[10px]"></i> Tersedia
                    </span>
                `;
            } else if (room.status === 'occupied') {
                statusBadge = `
                    <span class="px-2.5 py-1 bg-amber-500 text-white text-xs font-bold rounded-lg shadow-md flex items-center gap-1">
                        <i class="fas fa-clock text-[10px]"></i> Terpakai
                    </span>
                `;
            } else {
                statusBadge = `
                    <span class="px-2.5 py-1 bg-slate-600 text-white text-xs font-bold rounded-lg shadow-md flex items-center gap-1">
                        <i class="fas fa-tools text-[10px]"></i> Perawatan
                    </span>
                `;
            }

            let facsHtml = '';
            const facList = (room.facilities || '').split(',').map(f => f.trim()).filter(Boolean);
            facList.slice(0, 4).forEach(fac => {
                const highlightedFac = highlightText(fac, highlightQuery);
                facsHtml += `
                    <span class="text-[10px] font-medium px-2 py-0.5 bg-slate-50 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 rounded-md flex items-center gap-1">
                        <i class="fas fa-check text-emerald-500 text-[8px]"></i> ${highlightedFac}
                    </span>
                `;
            });

            html += `
                <div class="bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden flex flex-col hover:border-brand-300 dark:hover:border-brand-500 transition duration-200">
                    <div class="relative h-44 bg-slate-100 dark:bg-slate-900 overflow-hidden">
                        <img src="${escapeHtml(room.image)}" onerror="this.onerror=null; this.src='public/rooms/KalTim.jpeg';" class="w-full h-full object-cover" alt="${escapeHtml(room.name)}">
                        <div class="absolute top-3 right-3">
                            ${statusBadge}
                        </div>
                    </div>

                    <div class="p-4 flex-1 flex flex-col">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <h3 class="font-bold text-slate-900 dark:text-white text-base leading-snug">${displayName}</h3>
                            <span class="text-[11px] font-mono px-2 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded border border-slate-200 dark:border-slate-600 font-semibold">${displayCode}</span>
                        </div>

                        <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 mb-2">
                            <i class="fas fa-map-marker-alt text-rose-500"></i> ${displayLocation} (${displayFloor})
                        </p>

                        <div class="text-xs text-slate-600 dark:text-slate-300 mb-2 flex items-center gap-1 font-medium">
                            <i class="fas fa-users text-brand-600 dark:text-brand-400"></i> Kapasitas: <strong>${room.capacity} Orang</strong>
                        </div>

                        <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mb-3">
                            ${displayDesc}
                        </p>

                        <!-- Facilities Tags -->
                        <div class="flex flex-wrap gap-1 mb-4">
                            ${facsHtml}
                        </div>

                        <div class="pt-3 border-t border-slate-100 dark:border-slate-700/80 mt-auto flex items-center justify-between gap-2">
                            <button type="button" onclick="openBookingModal(${room.id})" class="flex-1 py-2 px-3 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg text-xs text-center transition shadow-sm ${room.status === 'maintenance' ? 'opacity-50 pointer-events-none' : ''}">
                                <i class="fas fa-calendar-check mr-1"></i> Pesan
                            </button>
                            <a href="calendar.php?room_id=${room.id}" class="py-2 px-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-lg text-xs transition" title="Lihat Jadwal">
                                <i class="fas fa-calendar-alt"></i> Jadwal
                            </a>
                        </div>
                    </div>
                </div>
            `;
        });

        roomCardsContainer.innerHTML = html;
    }

    // Event Listeners
    document.addEventListener('DOMContentLoaded', () => {
        if (roomSearchInput) {
            roomSearchInput.addEventListener('input', () => {
                const q = roomSearchInput.value.trim();
                updateRoomSuggestions(q);
                applyLiveRoomFilter();
            });

            roomSearchInput.addEventListener('focus', () => {
                const q = roomSearchInput.value.trim();
                if (q) updateRoomSuggestions(q);
            });
        }

        if (roomStatusFilter) roomStatusFilter.addEventListener('change', applyLiveRoomFilter);
        if (roomCapacityFilter) roomCapacityFilter.addEventListener('change', applyLiveRoomFilter);

        document.addEventListener('click', (e) => {
            if (roomSuggestionsBox && !e.target.closest('#roomSearchContainer')) {
                roomSuggestionsBox.classList.add('hidden');
            }
        });
    });

    // ==========================================
    // MODAL POPUP BOOKING LOGIC
    // ==========================================
    function openBookingModal(roomId = null) {
        const modal = document.getElementById('bookingModal');
        const modalCard = document.getElementById('bookingModalCard');
        const form = document.getElementById('bookingModalForm');
        const roomSelect = form ? form.querySelector('[data-booking-room]') : null;
        const errBox = document.getElementById('modalBookingError');
        
        if (errBox) errBox.classList.add('hidden');
        
        if (roomId && roomSelect) {
            roomSelect.value = roomId;
        }
        if (form && window.BookingFormUI) window.BookingFormUI.refresh(form);
        
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            modalCard.classList.remove('scale-95');
            modalCard.classList.add('scale-100');
        });
        document.body.classList.add('overflow-hidden');
    }

    function closeBookingModal() {
        const modal = document.getElementById('bookingModal');
        const modalCard = document.getElementById('bookingModalCard');
        if (!modal) return;
        
        modal.classList.add('opacity-0');
        if (modalCard) {
            modalCard.classList.remove('scale-100');
            modalCard.classList.add('scale-95');
        }
        
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }, 300);
    }

    async function handleBookingSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = document.getElementById('modalSubmitBtn');
        const errBox = document.getElementById('modalBookingError');
        const errMsg = document.getElementById('modalBookingErrorMessage');
        
        errBox.classList.add('hidden');
        
        const validation = window.BookingFormUI
            ? window.BookingFormUI.validate(form)
            : { valid: form.checkValidity(), message: 'Mohon periksa kembali form pemesanan.' };
        if (!validation.valid) {
            errMsg.textContent = validation.message;
            errBox.classList.remove('hidden');
            errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        
        // Loading state
        const originalContent = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> <span>Menyimpan...</span>';
        
        try {
            const formData = new FormData(form);
            const response = await fetch('booking.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.location.href = data.redirect || 'my_bookings.php';
            } else {
                errMsg.textContent = data.error || 'Terjadi kesalahan saat memproses pemesanan.';
                errBox.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
                errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } catch (error) {
            console.error('Booking submission error:', error);
            errMsg.textContent = 'Terjadi kesalahan saat menghubungi server. Silakan coba lagi.';
            errBox.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
        }
    }

    // Backdrop click & Esc key listener
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('bookingModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeBookingModal();
                }
            });
        }
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeBookingModal();
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
