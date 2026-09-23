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
    <div class="flex flex-wrap items-center gap-2.5">
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

<?php 
    $conflictCount = count($conflict_analyses ?? []); 
    $hasConflicts = ($conflictCount > 0);
?>

<!-- Tab Navigation Bar: Pemisah Konflik SAW vs Semua Booking -->
<div class="flex items-center gap-2 overflow-x-auto border-b border-slate-200 dark:border-slate-700 mb-6">
    <!-- Tab 1: SPK SAW Konflik Jadwal -->
    <button 
        type="button" 
        id="tabBtnConflicts" 
        onclick="switchBookingTab('conflicts')" 
        class="shrink-0 px-4 py-3 text-xs sm:text-sm font-bold border-b-2 transition flex items-center gap-2 -mb-px <?php echo $hasConflicts ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50/60 dark:bg-amber-950/30 rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'; ?>"
    >
        <i class="fas fa-balance-scale <?php echo $hasConflicts ? 'text-amber-500 animate-pulse' : ''; ?>"></i>
        <span>Konflik Jadwal & SPK SAW</span>
        <?php if ($hasConflicts): ?>
            <span class="px-2 py-0.5 bg-rose-500 text-white text-[10px] font-extrabold rounded-full animate-bounce shadow-sm">
                <?php echo $conflictCount; ?> Konflik
            </span>
        <?php else: ?>
            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 text-[10px] font-bold rounded-full">
                0
            </span>
        <?php endif; ?>
    </button>

    <!-- Tab 2: Semua Booking (Reguler) -->
    <button 
        type="button" 
        id="tabBtnAll" 
        onclick="switchBookingTab('all')" 
        class="shrink-0 px-4 py-3 text-xs sm:text-sm font-bold border-b-2 transition flex items-center gap-2 -mb-px <?php echo !$hasConflicts ? 'border-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50/60 dark:bg-amber-950/30 rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'; ?>"
    >
        <i class="fas fa-list"></i>
        <span>Semua Pemesanan (Reguler)</span>
        <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-[10px] font-bold rounded-full">
            <?php echo count($bookings); ?>
        </span>
    </button>
</div>

<!-- Partials Component Layer -->
<?php require __DIR__ . '/partials/_booking_conflicts_tab.php'; ?>
<?php require __DIR__ . '/partials/_booking_table.php'; ?>
<?php require __DIR__ . '/partials/_booking_modals.php'; ?>

<!-- Runtime Configuration & External Script Assets -->
<script>
    window.AdminBookingsConfig = {
        csrfField: '<?php echo addslashes(csrf_field()); ?>',
        initialBookings: <?php echo json_encode(array_map(function($b) use ($conflict_booking_ids) {
            $actTypes = SawService::ACTIVITY_TYPES;
            $actKey = $b['activity_type'] ?? 'internal_divisi';
            $actLabel = $actTypes[$actKey]['label'] ?? 'Rapat Internal';
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
                'status' => $b['status'],
                'status_reason' => $b['status_reason'] ?? null,
                'admin_notes' => $b['admin_notes'] ?? null,
                'activity_type_label' => $actLabel,
                'document_id' => (int)($b['document_id'] ?? 0),
                'document_name' => $b['document_name'] ?? '',
                'is_conflict' => isset($conflict_booking_ids[$b['id']])
            ];
        }, $bookings)); ?>,
        initialPendingCount: <?php echo (int)($pending_count ?? 0); ?>
    };
</script>
<script src="public/js/admin-bookings.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
