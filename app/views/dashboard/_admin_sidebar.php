<?php
$pendingRequests = $admin_dashboard['pending_requests'] ?? [];
$roomDisplays = $admin_dashboard['room_displays'] ?? [];
$pendingCount = (int)($admin_dashboard['pending_count'] ?? 0);
$onlineCount = count(array_filter(
    $roomDisplays,
    static fn(array $room): bool => ($room['display_status'] ?? '') === 'online'
));
$configuredDisplayCount = count(array_filter(
    $roomDisplays,
    static fn(array $room): bool => ($room['display_status'] ?? '') !== 'unconfigured'
));

$displayStatusMeta = [
    'online' => [
        'label' => 'Online',
        'dot' => 'bg-emerald-500',
        'text' => 'text-emerald-600 dark:text-emerald-400',
    ],
    'offline' => [
        'label' => 'Offline',
        'dot' => 'bg-rose-500',
        'text' => 'text-rose-600 dark:text-rose-400',
    ],
    'unconfigured' => [
        'label' => 'Belum diatur',
        'dot' => 'bg-slate-400',
        'text' => 'text-slate-500 dark:text-slate-400',
    ],
];
?>

<section aria-labelledby="pendingApprovalTitle" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm p-6">
    <div class="flex items-center justify-between gap-3 mb-5 pb-4 border-b border-slate-100 dark:border-slate-700">
        <div class="flex items-center gap-2 min-w-0">
            <i class="fas fa-clipboard-check text-amber-500"></i>
            <h3 id="pendingApprovalTitle" class="font-bold text-slate-900 dark:text-white text-base">Persetujuan Peminjaman</h3>
        </div>
        <span class="shrink-0 min-w-7 h-7 px-2 inline-flex items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 text-xs font-bold text-amber-700 dark:text-amber-300">
            <?php echo $pendingCount; ?>
        </span>
    </div>

    <?php if (empty($pendingRequests)): ?>
        <div class="py-5 text-center">
            <i class="fas fa-check-circle text-2xl text-emerald-400 mb-2"></i>
            <p class="text-xs text-slate-500 dark:text-slate-400">Tidak ada pengajuan yang menunggu persetujuan.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($pendingRequests as $request): ?>
                <article class="rounded-xl border border-slate-200/80 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h4 class="truncate text-sm font-bold text-slate-800 dark:text-slate-100" title="<?php echo htmlspecialchars($request['title']); ?>">
                                <?php echo htmlspecialchars($request['title']); ?>
                            </h4>
                            <p class="mt-2 flex items-center gap-2 truncate text-[11px] text-slate-500 dark:text-slate-400">
                                <i class="fas fa-door-open w-4 shrink-0 text-center text-brand-500"></i>
                                <span class="truncate"><?php echo htmlspecialchars($request['room_name']); ?></span>
                            </p>
                        </div>
                        <?php if (!empty($request['has_conflict'])): ?>
                            <span class="shrink-0 rounded-md bg-rose-100 dark:bg-rose-950/60 px-2 py-1 text-[9px] font-bold uppercase text-rose-700 dark:text-rose-300">Bentrok</span>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4 grid gap-2 rounded-lg border border-slate-200/70 dark:border-slate-700 bg-white/70 dark:bg-slate-800/70 px-3 py-2.5 text-[11px] text-slate-500 dark:text-slate-400">
                        <span class="flex items-center gap-2 leading-5">
                            <i class="fas fa-calendar-day w-4 shrink-0 text-center text-slate-400"></i>
                            <span><?php echo format_date($request['date']); ?></span>
                        </span>
                        <span class="flex items-center gap-2 leading-5">
                            <i class="fas fa-clock w-4 shrink-0 text-center text-slate-400"></i>
                            <span><?php echo format_time($request['start_time']); ?> - <?php echo format_time($request['end_time']); ?> WIB</span>
                        </span>
                        <span class="flex min-w-0 items-center gap-2 leading-5">
                            <i class="fas fa-user w-4 shrink-0 text-center text-slate-400"></i>
                            <span class="truncate"><?php echo htmlspecialchars($request['user_name']); ?></span>
                        </span>
                    </div>

                    <div class="mt-4 flex items-center gap-3 border-t border-slate-200/70 dark:border-slate-700 pt-4">
                        <?php if (!empty($request['has_conflict'])): ?>
                            <a href="admin_bookings.php" class="flex-1 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 py-2.5 text-center text-xs font-bold text-rose-700 dark:text-rose-300 transition hover:bg-rose-100 dark:hover:bg-rose-950/70">
                                <i class="fas fa-code-branch mr-1"></i>Analisis
                            </a>
                        <?php else: ?>
                            <form method="POST" action="admin_bookings.php" class="flex-1">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="booking_id" value="<?php echo (int)$request['id']; ?>">
                                <input type="hidden" name="status" value="confirmed">
                                <input type="hidden" name="return_to" value="dashboard.php">
                                <button type="submit" class="w-full rounded-lg bg-emerald-600 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-700">
                                    <i class="fas fa-check mr-1"></i>Setujui
                                </button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="admin_bookings.php" class="flex-1" onsubmit="return confirm('Tolak pengajuan booking ini?')">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="booking_id" value="<?php echo (int)$request['id']; ?>">
                            <input type="hidden" name="status" value="cancelled">
                            <input type="hidden" name="return_to" value="dashboard.php">
                            <button type="submit" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-300 transition hover:border-rose-300 hover:text-rose-600">
                                <i class="fas fa-times mr-1"></i>Tolak
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <a href="admin_bookings.php?status=pending" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-950/40 py-2.5 text-xs font-bold text-amber-700 dark:text-amber-300 transition hover:bg-amber-100 dark:hover:bg-amber-950/70">
        <i class="fas fa-list-check"></i> Lihat Semua Pengajuan
    </a>
</section>

<section aria-labelledby="displayMonitoringTitle" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm p-6">
    <div class="flex items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100 dark:border-slate-700">
        <div class="flex items-center gap-2 min-w-0">
            <i class="fas fa-display text-brand-600 dark:text-brand-400"></i>
            <h3 id="displayMonitoringTitle" class="font-bold text-slate-900 dark:text-white text-base">Monitoring Display</h3>
        </div>
        <span class="shrink-0 text-[10px] font-bold text-slate-500 dark:text-slate-400"><?php echo $onlineCount; ?>/<?php echo $configuredDisplayCount; ?> online</span>
    </div>

    <div class="max-h-72 space-y-1.5 overflow-y-auto pr-1">
        <?php foreach ($roomDisplays as $room): ?>
            <?php $displayMeta = $displayStatusMeta[$room['display_status']] ?? $displayStatusMeta['unconfigured']; ?>
            <div class="flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-900/50 transition">
                <div class="min-w-0">
                    <div class="truncate text-xs font-semibold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($room['name']); ?></div>
                    <div class="mt-0.5 truncate text-[10px] text-slate-400"><?php echo htmlspecialchars($room['display_name'] ?: $room['code']); ?></div>
                </div>
                <div class="flex shrink-0 items-center gap-1.5 text-[10px] font-bold <?php echo $displayMeta['text']; ?>">
                    <span class="h-2 w-2 rounded-full <?php echo $displayMeta['dot']; ?>"></span>
                    <?php echo $displayMeta['label']; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <a href="admin_displays.php" class="mt-4 inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-brand-200 dark:border-brand-800 bg-brand-50 dark:bg-brand-950/50 py-2 text-xs font-bold text-brand-600 dark:text-brand-400 transition hover:bg-brand-100 dark:hover:bg-brand-900/60">
        <i class="fas fa-sliders"></i> Kelola Monitor Display
    </a>
</section>
