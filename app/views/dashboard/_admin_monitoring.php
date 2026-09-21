<?php
$adminSummary = $admin_dashboard['summary'] ?? [];
$pendingRequests = $admin_dashboard['pending_requests'] ?? [];
$roomMonitoring = $admin_dashboard['room_monitoring'] ?? [];
$operationalAlerts = $admin_dashboard['alerts'] ?? [];

$statusMeta = [
    'available' => [
        'label' => 'Tersedia',
        'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800',
        'dot' => 'bg-emerald-500',
    ],
    'occupied' => [
        'label' => 'Digunakan',
        'badge' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800',
        'dot' => 'bg-rose-500 animate-pulse',
    ],
    'awaiting_check_in' => [
        'label' => 'Menunggu check-in',
        'badge' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800',
        'dot' => 'bg-amber-500 animate-pulse',
    ],
    'maintenance' => [
        'label' => 'Maintenance',
        'badge' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600',
        'dot' => 'bg-slate-400',
    ],
];

$alertMeta = [
    'danger' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900',
    'warning' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900',
    'info' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-900',
];
?>

<section aria-labelledby="operationalSummaryTitle" class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <h2 id="operationalSummaryTitle" class="text-sm font-bold text-slate-900 dark:text-white">Ringkasan Operasional</h2>
            </div>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Kondisi booking dan ruangan yang membutuhkan perhatian hari ini.</p>
        </div>
        <button type="button" onclick="window.location.reload()" class="w-fit rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-[10px] font-bold text-slate-500 dark:text-slate-400 transition hover:border-brand-300 hover:text-brand-600 dark:hover:border-brand-700 dark:hover:text-brand-300" title="Perbarui seluruh data operasional">
            <i class="fas fa-sync-alt mr-1 text-emerald-500"></i> Refresh data · <?php echo date('H:i'); ?> WIB
        </button>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <a href="admin_bookings.php?status=pending" class="group rounded-2xl border border-amber-200 dark:border-amber-900 bg-white dark:bg-slate-800 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Perlu approval</div>
                    <div class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400"><?php echo (int)($adminSummary['pending_requests'] ?? 0); ?></div>
                </div>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-300"><i class="fas fa-inbox"></i></span>
            </div>
            <div class="mt-2 text-[10px] font-semibold text-amber-600 dark:text-amber-400">Buka antrean <i class="fas fa-arrow-right ml-1 transition group-hover:translate-x-0.5"></i></div>
        </a>

        <div class="rounded-2xl border border-rose-200 dark:border-rose-900 bg-white dark:bg-slate-800 p-4 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Berlangsung</div>
                    <div class="mt-1 text-2xl font-black text-rose-600 dark:text-rose-400"><?php echo (int)($adminSummary['ongoing_meetings'] ?? 0); ?></div>
                </div>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-300"><i class="fas fa-wave-square"></i></span>
            </div>
            <div class="mt-2 text-[10px] font-medium text-slate-400">Ruang sedang digunakan</div>
        </div>

        <div class="rounded-2xl border border-blue-200 dark:border-blue-900 bg-white dark:bg-slate-800 p-4 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Menunggu check-in</div>
                    <div class="mt-1 text-2xl font-black text-blue-600 dark:text-blue-400"><?php echo (int)($adminSummary['awaiting_check_in'] ?? 0); ?></div>
                </div>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-300"><i class="fas fa-user-clock"></i></span>
            </div>
            <div class="mt-2 text-[10px] font-medium text-slate-400">Dalam jendela check-in</div>
        </div>

        <a href="admin_history.php?status=completed" class="group rounded-2xl border border-violet-200 dark:border-violet-900 bg-white dark:bg-slate-800 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">No-show hari ini</div>
                    <div class="mt-1 text-2xl font-black text-violet-600 dark:text-violet-400"><?php echo (int)($adminSummary['no_show_today'] ?? 0); ?></div>
                </div>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-950/60 text-violet-600 dark:text-violet-300"><i class="fas fa-user-slash"></i></span>
            </div>
            <div class="mt-2 text-[10px] font-semibold text-violet-600 dark:text-violet-400">Lihat riwayat <i class="fas fa-arrow-right ml-1 transition group-hover:translate-x-0.5"></i></div>
        </a>

        <div class="col-span-2 rounded-2xl border border-emerald-200 dark:border-emerald-900 bg-white dark:bg-slate-800 p-4 shadow-sm lg:col-span-1">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ruang tersedia</div>
                    <div class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400">
                        <?php echo (int)($adminSummary['available_rooms'] ?? 0); ?><span class="text-sm font-bold text-slate-400">/<?php echo (int)($adminSummary['total_rooms'] ?? 0); ?></span>
                    </div>
                </div>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-300"><i class="fas fa-door-open"></i></span>
            </div>
            <div class="mt-2 text-[10px] font-medium text-slate-400">Ketersediaan real-time</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 px-5 py-4">
                <div>
                    <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-white">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-300"><i class="fas fa-bolt"></i></span>
                        Perlu Tindakan
                    </h3>
                    <p class="mt-1 text-[10px] text-slate-400">Pengajuan terdekat ditampilkan lebih dahulu.</p>
                </div>
                <a href="admin_bookings.php?status=pending" class="text-[10px] font-bold text-brand-600 dark:text-brand-400 hover:underline">Lihat semua</a>
            </div>

            <?php if (empty($pendingRequests)): ?>
                <div class="flex min-h-48 flex-col items-center justify-center px-5 py-8 text-center">
                    <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-500"><i class="fas fa-check-double text-xl"></i></span>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Semua permintaan sudah diproses</p>
                    <p class="mt-1 text-xs text-slate-400">Tidak ada pengajuan yang menunggu persetujuan.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-100 dark:divide-slate-700/80">
                    <?php foreach ($pendingRequests as $request): ?>
                        <?php
                        $waitMinutes = (int)$request['wait_minutes'];
                        if ($waitMinutes >= 1440) {
                            $waitLabel = floor($waitMinutes / 1440) . ' hari';
                        } elseif ($waitMinutes >= 60) {
                            $waitLabel = floor($waitMinutes / 60) . ' jam';
                        } else {
                            $waitLabel = max(1, $waitMinutes) . ' menit';
                        }
                        ?>
                        <div class="flex flex-col gap-3 px-5 py-4 transition hover:bg-slate-50/70 dark:hover:bg-slate-700/30 sm:flex-row sm:items-center">
                            <div class="min-w-0 flex-1">
                                <div class="mb-1.5 flex flex-wrap items-center gap-2">
                                    <span class="rounded-md bg-brand-50 dark:bg-brand-950/50 px-2 py-0.5 text-[10px] font-bold text-brand-700 dark:text-brand-300"><?php echo format_date($request['date']); ?></span>
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400"><?php echo format_time($request['start_time']); ?>–<?php echo format_time($request['end_time']); ?></span>
                                    <?php if (!empty($request['has_conflict'])): ?>
                                        <span class="rounded-md bg-rose-100 dark:bg-rose-950/60 px-2 py-0.5 text-[9px] font-bold uppercase text-rose-700 dark:text-rose-300"><i class="fas fa-exclamation-triangle mr-1"></i>Bentrok</span>
                                    <?php endif; ?>
                                </div>
                                <h4 class="truncate text-sm font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($request['title']); ?></h4>
                                <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-[10px] text-slate-500 dark:text-slate-400">
                                    <span><i class="fas fa-door-open mr-1 text-slate-400"></i><?php echo htmlspecialchars($request['room_name']); ?></span>
                                    <span><i class="fas fa-user mr-1 text-slate-400"></i><?php echo htmlspecialchars($request['user_name']); ?></span>
                                    <span><i class="fas fa-hourglass-half mr-1 text-amber-500"></i>Menunggu <?php echo $waitLabel; ?></span>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <?php if (!empty($request['has_conflict'])): ?>
                                    <a href="admin_bookings.php" class="rounded-lg border border-rose-200 dark:border-rose-900 px-3 py-2 text-[10px] font-bold text-rose-600 dark:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">Analisis SAW</a>
                                <?php else: ?>
                                    <form method="POST" action="admin_bookings.php">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="booking_id" value="<?php echo (int)$request['id']; ?>">
                                        <input type="hidden" name="status" value="confirmed">
                                        <input type="hidden" name="return_to" value="dashboard.php">
                                        <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-2 text-[10px] font-bold text-white shadow-sm transition hover:bg-emerald-700"><i class="fas fa-check mr-1"></i>Setujui</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="admin_bookings.php" onsubmit="return confirm('Tolak pengajuan booking ini?')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="booking_id" value="<?php echo (int)$request['id']; ?>">
                                    <input type="hidden" name="status" value="cancelled">
                                    <input type="hidden" name="return_to" value="dashboard.php">
                                    <button type="submit" class="rounded-lg border border-slate-200 dark:border-slate-600 px-3 py-2 text-[10px] font-bold text-slate-600 dark:text-slate-300 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600 dark:hover:border-rose-900 dark:hover:bg-rose-950/40"><i class="fas fa-times mr-1"></i>Tolak</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
            <div class="border-b border-slate-100 dark:border-slate-700 px-5 py-4">
                <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-white">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-300"><i class="fas fa-triangle-exclamation"></i></span>
                    Peringatan Sistem
                </h3>
                <p class="mt-1 text-[10px] text-slate-400">Kondisi yang perlu diperiksa admin.</p>
            </div>
            <?php if (empty($operationalAlerts)): ?>
                <div class="flex min-h-48 flex-col items-center justify-center p-6 text-center">
                    <i class="fas fa-shield-halved mb-3 text-3xl text-emerald-500"></i>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Semua sistem normal</p>
                    <p class="mt-1 text-xs text-slate-400">Tidak ada peringatan aktif.</p>
                </div>
            <?php else: ?>
                <div class="space-y-2 p-4">
                    <?php foreach ($operationalAlerts as $alert): ?>
                        <a href="<?php echo htmlspecialchars($alert['url']); ?>" class="flex items-start gap-3 rounded-xl border p-3 transition hover:shadow-sm <?php echo $alertMeta[$alert['type']] ?? $alertMeta['info']; ?>">
                            <i class="fas <?php echo htmlspecialchars($alert['icon']); ?> mt-0.5 w-4 text-center"></i>
                            <div class="min-w-0">
                                <div class="text-xs font-bold"><?php echo htmlspecialchars($alert['title']); ?></div>
                                <div class="mt-0.5 text-[10px] leading-relaxed opacity-80"><?php echo htmlspecialchars($alert['description']); ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 dark:border-slate-700 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-white">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-300"><i class="fas fa-display"></i></span>
                    Monitoring Ruangan Saat Ini
                </h3>
                <p class="mt-1 text-[10px] text-slate-400">Status penggunaan, jadwal berikutnya, dan koneksi display.</p>
            </div>
            <div class="flex gap-2">
                <a href="display_lobby.php" target="_blank" class="rounded-lg border border-blue-200 dark:border-blue-800 px-3 py-2 text-[10px] font-bold text-blue-600 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-950/40 transition"><i class="fas fa-desktop mr-1"></i>Monitor Lobby</a>
                <a href="admin_displays.php" class="rounded-lg bg-slate-100 dark:bg-slate-700 px-3 py-2 text-[10px] font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition">Kelola Display</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] text-left">
                <thead class="bg-slate-50/80 dark:bg-slate-900/50 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-5 py-3">Ruangan</th>
                        <th class="px-4 py-3">Status sekarang</th>
                        <th class="px-4 py-3">Aktivitas</th>
                        <th class="px-4 py-3">Jadwal berikutnya</th>
                        <th class="px-4 py-3">Display</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/80">
                    <?php foreach ($roomMonitoring as $room): ?>
                        <?php
                        $roomStatus = $statusMeta[$room['operational_status']] ?? $statusMeta['available'];
                        $isOccupied = $room['operational_status'] === 'occupied';
                        $isAwaiting = $room['operational_status'] === 'awaiting_check_in';
                        $remainingMinutes = $isOccupied
                            ? max(0, (int)ceil((strtotime(date('Y-m-d') . ' ' . $room['current_end_time']) - time()) / 60))
                            : 0;
                        ?>
                        <tr class="transition hover:bg-slate-50/70 dark:hover:bg-slate-700/30">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($room['name']); ?></div>
                                <div class="mt-0.5 text-[10px] text-slate-400"><span class="font-mono font-bold"><?php echo htmlspecialchars($room['code']); ?></span> · <?php echo htmlspecialchars($room['location']); ?></div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-bold <?php echo $roomStatus['badge']; ?>">
                                    <span class="h-1.5 w-1.5 rounded-full <?php echo $roomStatus['dot']; ?>"></span>
                                    <?php echo $roomStatus['label']; ?>
                                </span>
                            </td>
                            <td class="max-w-64 px-4 py-4">
                                <?php if ($isOccupied): ?>
                                    <div class="truncate text-xs font-bold text-slate-800 dark:text-slate-100"><?php echo htmlspecialchars($room['current_title']); ?></div>
                                    <div class="mt-1 text-[10px] text-slate-500 dark:text-slate-400"><?php echo format_time($room['current_start_time']); ?>–<?php echo format_time($room['current_end_time']); ?> · tersisa <?php echo $remainingMinutes; ?> menit</div>
                                <?php elseif ($isAwaiting): ?>
                                    <div class="truncate text-xs font-bold text-amber-700 dark:text-amber-300"><?php echo htmlspecialchars($room['awaiting_title']); ?></div>
                                    <div class="mt-1 text-[10px] text-slate-500 dark:text-slate-400">Mulai <?php echo format_time($room['awaiting_start_time']); ?> · belum check-in</div>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">Tidak ada rapat aktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="max-w-56 px-4 py-4">
                                <?php if (!empty($room['next_booking_id'])): ?>
                                    <div class="truncate text-xs font-semibold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($room['next_title']); ?></div>
                                    <div class="mt-1 text-[10px] text-slate-400"><?php echo $room['next_date'] === date('Y-m-d') ? 'Hari ini' : format_date($room['next_date']); ?>, <?php echo format_time($room['next_start_time']); ?></div>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">Belum ada jadwal</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <?php if ($room['display_status'] === 'online'): ?>
                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400"><span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>Online</span>
                                <?php elseif ($room['display_status'] === 'offline'): ?>
                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-rose-600 dark:text-rose-400"><span class="h-2 w-2 rounded-full bg-rose-500"></span>Offline</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-slate-400"><span class="h-2 w-2 rounded-full bg-slate-300"></span>Belum diatur</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="calendar.php?room_id=<?php echo (int)$room['id']; ?>" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-600 text-slate-500 dark:text-slate-300 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-600 dark:hover:border-brand-800 dark:hover:bg-brand-950/40" title="Lihat kalender ruangan"><i class="fas fa-calendar-alt text-xs"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
