<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- Chart.js 4 CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white dark:bg-slate-800 p-4 sm:p-6 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-1">
                <i class="fas fa-chart-pie"></i> Executive Analytics & Insights
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Statistik & Analisis Penggunaan Ruangan</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">
                Dashboard analitik komprehensif untuk memantau tren pemesanan, tingkat utilisasi ruangan, jam sibuk, dan aktivitas divisi.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <button onclick="window.print()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold rounded-xl text-xs transition flex items-center gap-2 shadow-sm">
                <i class="fas fa-print"></i>
                <span>Cetak Laporan</span>
            </button>
        </div>
    </div>

    <!-- Filter & Time Range Bar -->
    <form method="GET" action="admin_statistics.php" class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors">
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
            <!-- Period Preset Buttons -->
            <div class="flex flex-wrap items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-900/60 rounded-xl">
                <?php
                    $curPeriod = $filters['period'] ?? 'this_year';
                    $periods = [
                        'this_month' => 'Bulan Ini',
                        'last_month' => 'Bulan Lalu',
                        'last_3_months' => '3 Bulan Terakhir',
                        'this_year' => 'Tahun Ini',
                        'all_time' => 'Semua Waktu'
                    ];
                ?>
                <?php foreach ($periods as $key => $label): ?>
                    <button 
                        type="submit" 
                        name="period" 
                        value="<?php echo $key; ?>" 
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?php echo ($curPeriod === $key && empty($filters['start_date']) && empty($filters['end_date'])) ? 'bg-white dark:bg-slate-800 text-brand-600 dark:text-brand-400 shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'; ?>">
                        <?php echo $label; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Custom Date Range & Room Filter -->
            <div class="grid w-full grid-cols-1 gap-2.5 sm:grid-cols-2 lg:flex lg:w-auto lg:flex-wrap lg:items-center">
                <div class="flex w-full items-center gap-1.5 bg-slate-50 dark:bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 lg:w-auto">
                    <span class="text-[11px] font-bold text-slate-400 uppercase">Dari:</span>
                    <input 
                        type="date" 
                        name="start_date" 
                        value="<?php echo htmlspecialchars($filters['start_date'] ?? ''); ?>" 
                        class="bg-transparent text-xs text-slate-700 dark:text-slate-200 focus:outline-none">
                </div>

                <div class="flex w-full items-center gap-1.5 bg-slate-50 dark:bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 lg:w-auto">
                    <span class="text-[11px] font-bold text-slate-400 uppercase">Sampai:</span>
                    <input 
                        type="date" 
                        name="end_date" 
                        value="<?php echo htmlspecialchars($filters['end_date'] ?? ''); ?>" 
                        class="bg-transparent text-xs text-slate-700 dark:text-slate-200 focus:outline-none">
                </div>

                <div class="w-full lg:w-44">
                    <select name="room_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer">
                        <option value="0">-- Semua Ruangan --</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo ($filters['room_id'] ?? 0) == $r['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['name']); ?> (<?php echo htmlspecialchars($r['code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-sm lg:w-auto">
                    <i class="fas fa-filter"></i>
                    <span>Terapkan</span>
                </button>

                <?php if (!empty($filters['start_date']) || !empty($filters['end_date']) || !empty($filters['room_id']) || $curPeriod !== 'this_year'): ?>
                    <a href="admin_statistics.php" class="p-2 text-slate-400 hover:text-rose-500 rounded-xl transition text-xs" title="Reset Filter">
                        <i class="fas fa-undo"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Top KPI Cards Grid -->
    <div class="grid grid-cols-1 min-[420px]:grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <!-- KPI 1: Total Bookings -->
        <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Rapat</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white"><?php echo number_format($kpi['total_bookings']); ?></div>
            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 flex items-center gap-1">
                <span class="text-emerald-600 dark:text-emerald-400 font-semibold"><?php echo $kpi['confirmed_count']; ?> Sah</span> • 
                <span class="text-amber-600 dark:text-amber-400 font-semibold"><?php echo $kpi['pending_count']; ?> Pending</span>
            </div>
        </div>

        <!-- KPI 2: Total Hours -->
        <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Durasi Rapat</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white"><?php echo $kpi['total_hours']; ?> <span class="text-xs font-normal text-slate-400">Jam</span></div>
            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                Rata-rata <strong class="text-slate-700 dark:text-slate-300"><?php echo $kpi['avg_duration_minutes']; ?> mnt</strong> / rapat
            </div>
        </div>

        <!-- KPI 3: Total Attendees -->
        <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Peserta</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white"><?php echo number_format($kpi['total_attendees']); ?></div>
            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                Rata-rata <strong class="text-slate-700 dark:text-slate-300"><?php echo $kpi['avg_attendees']; ?></strong> org / rapat
            </div>
        </div>

        <!-- KPI 4: Approval Rate -->
        <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Tingkat Setuju</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400"><?php echo $kpi['approval_rate']; ?>%</div>
            <div class="w-full bg-slate-100 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden mt-2">
                <div class="data-progress-bar bg-emerald-500 h-full rounded-full transition-all duration-500" style="--progress-value: <?php echo min(100, $kpi['approval_rate']); ?>%"></div>
            </div>
        </div>

        <!-- KPI 5: Active Rooms Used -->
        <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ruangan Aktif</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-door-open"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white"><?php echo $kpi['active_rooms_used']; ?> <span class="text-xs font-normal text-slate-400">/ <?php echo $kpi['total_rooms']; ?></span></div>
            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                Ruangan telah terpakai
            </div>
        </div>

        <!-- KPI 6: Top Department -->
        <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Divisi Teraktif</span>
                <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-900/40 text-teal-600 dark:text-teal-400 flex items-center justify-center text-sm font-bold">
                    <i class="fas fa-building"></i>
                </div>
            </div>
            <div class="text-sm font-black text-slate-900 dark:text-white truncate" title="<?php echo htmlspecialchars($stats['dept_distribution']['labels'][0] ?? 'N/A'); ?>">
                <?php echo htmlspecialchars($stats['dept_distribution']['labels'][0] ?? 'Belum ada data'); ?>
            </div>
            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                <?php echo ($stats['dept_distribution']['data'][0] ?? 0); ?> pertemuan tercatat
            </div>
        </div>
    </div>

    <!-- Charts Grid Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Monthly Booking Trend Line/Bar Chart (2 Cols) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-chart-line text-brand-600 dark:text-brand-400"></i>
                        Tren Aktivitas & Durasi Pemesanan
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Jumlah agenda rapat dan total akumulasi jam rapat tiap bulan.</p>
                </div>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>

        <!-- Department Distribution Doughnut Chart (1 Col) -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors flex flex-col justify-between">
            <div class="mb-3">
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-pie-chart text-emerald-600 dark:text-emerald-400"></i>
                    Proporsi Divisi Pemohon
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Persentase pengajuan rapat berdasarkan unit kerja.</p>
            </div>
            <div class="relative h-60 w-full flex items-center justify-center">
                <?php if (empty($stats['dept_distribution']['data'])): ?>
                    <div class="text-center text-slate-400 text-xs py-10">Belum ada data pemesanan.</div>
                <?php else: ?>
                    <canvas id="deptDistributionChart"></canvas>
                <?php endif; ?>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-700/60 flex flex-wrap gap-2 text-[11px] justify-center">
                <?php foreach (array_slice($stats['dept_distribution']['labels'], 0, 4) as $idx => $deptName): ?>
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-700/60 text-slate-700 dark:text-slate-300">
                        <span class="data-color-swatch w-2 h-2 rounded-full" style="--swatch-color: <?php echo ['#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6'][$idx % 5]; ?>"></span>
                        <?php echo htmlspecialchars($deptName); ?> (<?php echo $stats['dept_distribution']['percentages'][$idx] ?? 0; ?>%)
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Charts Grid Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Room Popularity & Utilization Horizontal Bar Chart -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-layer-group text-amber-500"></i>
                        Popularitas Ruangan Rapat
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Frekuensi pertemuan yang diselenggarakan di tiap ruangan.</p>
                </div>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="roomUsageChart"></canvas>
            </div>
        </div>

        <!-- Peak Hours Bar Chart -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-fire text-rose-500"></i>
                        Distribusi Jam Sibuk Rapat (Peak Hours)
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Pola waktu tersering ruangan digunakan (pukul 08:00 - 18:00 WIB).</p>
                </div>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="peakHoursChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Day-of-Week Distribution Strip -->
    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-calendar-week text-indigo-500"></i>
                    Intensitas Hari Rapat Dalam Seminggu
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Perbandingan beban agenda ruang rapat dari hari Senin hingga Minggu.</p>
            </div>
        </div>
        <div class="grid grid-cols-2 min-[480px]:grid-cols-4 sm:grid-cols-7 gap-2.5">
            <?php 
                $maxDayCount = max(1, ...($stats['day_distribution']['data'] ?: [1]));
                foreach ($stats['day_distribution']['labels'] as $idx => $dName): 
                    $cnt = $stats['day_distribution']['data'][$idx] ?? 0;
                    $intensity = round(($cnt / $maxDayCount) * 100);
            ?>
                <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-200/60 dark:border-slate-700/60 text-center">
                    <div class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider"><?php echo $dName; ?></div>
                    <div class="text-xl font-black text-slate-900 dark:text-white my-1"><?php echo $cnt; ?></div>
                    <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden mt-1">
                        <div class="data-progress-bar bg-brand-500 h-full rounded-full" style="--progress-value: <?php echo $intensity; ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Detailed Room Performance & Utilization Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm overflow-hidden transition-colors">
        <div class="p-5 border-b border-slate-100 dark:border-slate-700/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-table text-slate-500"></i>
                    Matriks Analisis Utilisasi Ruangan Rapat
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Rincian performa operasional, kapasitas, dan beban jam kerja tiap ruangan.</p>
            </div>
            <span class="text-xs font-semibold px-3 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl border border-slate-200 dark:border-slate-600 w-fit">
                Total <?php echo count($stats['room_stats']); ?> Ruangan
            </span>
        </div>

        <div class="responsive-table-shell">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 uppercase text-[11px] font-bold tracking-wider border-b border-slate-100 dark:border-slate-700/80">
                    <tr>
                        <th class="py-3.5 px-5">Ruang Rapat</th>
                        <th class="py-3.5 px-5">Lokasi & Lantai</th>
                        <th class="py-3.5 px-5 text-center">Kapasitas</th>
                        <th class="py-3.5 px-5 text-center">Total Rapat</th>
                        <th class="py-3.5 px-5 text-center">Total Durasi</th>
                        <th class="py-3.5 px-5 text-center">Rata-rata Peserta</th>
                        <th class="py-3.5 px-5">Estimasi Utilisasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/80 font-medium">
                    <?php if (empty($stats['room_stats'])): ?>
                        <tr>
                            <td colspan="7" class="text-center py-10 text-slate-400">Belum ada data ruangan tercatat.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($stats['room_stats'] as $rs): ?>
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition">
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-sm">
                                            <i class="fas fa-door-open"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($rs['name']); ?></div>
                                            <span class="text-[10px] font-mono font-bold px-1.5 py-0.2 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded border border-slate-200 dark:border-slate-600">
                                                <?php echo htmlspecialchars($rs['code']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-5 text-xs">
                                    <div class="text-slate-800 dark:text-slate-200 font-medium"><?php echo htmlspecialchars($rs['location']); ?></div>
                                    <div class="text-slate-400 text-[11px]"><?php echo htmlspecialchars($rs['floor']); ?></div>
                                </td>
                                <td class="py-4 px-5 text-center">
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold font-mono">
                                        <?php echo $rs['capacity']; ?> Kursi
                                    </span>
                                </td>
                                <td class="py-4 px-5 text-center">
                                    <span class="font-black text-slate-900 dark:text-white text-base"><?php echo $rs['booking_count']; ?></span>
                                    <span class="text-xs text-slate-400 block font-normal"><?php echo $rs['confirmed_count']; ?> sah</span>
                                </td>
                                <td class="py-4 px-5 text-center">
                                    <span class="font-bold text-slate-800 dark:text-slate-200"><?php echo $rs['total_hours']; ?></span>
                                    <span class="text-xs text-slate-400 font-normal">Jam</span>
                                </td>
                                <td class="py-4 px-5 text-center">
                                    <span class="font-bold text-slate-800 dark:text-slate-200"><?php echo $rs['avg_attendees']; ?></span>
                                    <span class="text-xs text-slate-400 font-normal">Org/Sesi</span>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="w-full max-w-xs">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-bold text-slate-700 dark:text-slate-300"><?php echo $rs['utilization_rate']; ?>%</span>
                                        </div>
                                        <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                            <div class="data-progress-bar h-full rounded-full transition-all duration-500 <?php echo $rs['utilization_rate'] > 70 ? 'bg-rose-500' : ($rs['utilization_rate'] > 30 ? 'bg-emerald-500' : 'bg-brand-500'); ?>" style="--progress-value: <?php echo min(100, $rs['utilization_rate']); ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Organizers / Bookers Grid -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-trophy text-amber-500"></i>
                    Inisiator & Pemesan Rapat Teraktif
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Daftar staf/organizer dengan intensitas pengajuan booking tertinggi.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php if (empty($stats['top_organizers'])): ?>
                <div class="col-span-4 text-center py-8 text-slate-400 text-xs">Belum ada data organizer.</div>
            <?php else: ?>
                <?php foreach ($stats['top_organizers'] as $rank => $org): ?>
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-700/60 flex items-start gap-3.5">
                        <div class="w-8 h-8 rounded-lg <?php echo $rank === 0 ? 'bg-amber-400 text-amber-950 font-black' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold'; ?> flex items-center justify-center shrink-0 text-sm">
                            #<?php echo $rank + 1; ?>
                        </div>
                        <div class="overflow-hidden flex-1">
                            <div class="font-bold text-slate-900 dark:text-white text-sm truncate"><?php echo htmlspecialchars($org['name']); ?></div>
                            <div class="text-xs text-brand-600 dark:text-brand-400 font-semibold truncate"><?php echo htmlspecialchars($org['department']); ?></div>
                            <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-2">
                                <span><strong><?php echo $org['booking_count']; ?></strong> Rapat</span> •
                                <span><strong><?php echo $org['total_hours']; ?></strong> Jam</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js Setup and Reactive Dark Mode Palette Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    /** Memeriksa apakah tema gelap sedang aktif. */
    const isDark = () => document.documentElement.classList.contains('dark');

    /** Menentukan warna teks grafik sesuai tema aktif. */
    const getTextColor = () => isDark() ? '#94a3b8' : '#64748b';
    /** Menentukan warna garis kisi grafik sesuai tema aktif. */
    const getGridColor = () => isDark() ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.8)';
    /** Menentukan warna judul grafik sesuai tema aktif. */
    const getTitleColor = () => isDark() ? '#f8fafc' : '#0f172a';

    // 1. Monthly Trend Chart
    const monthlyCtx = document.getElementById('monthlyTrendChart');
    let monthlyChart = null;
    if (monthlyCtx) {
        monthlyChart = new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($stats['monthly_trend']['labels']); ?>,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Rapat Sah (Disetujui)',
                        data: <?php echo json_encode($stats['monthly_trend']['confirmed']); ?>,
                        backgroundColor: '#3b82f6',
                        borderRadius: 6,
                        barPercentage: 0.6,
                        yAxisID: 'y'
                    },
                    {
                        type: 'bar',
                        label: 'Menunggu Persetujuan',
                        data: <?php echo json_encode($stats['monthly_trend']['pending']); ?>,
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                        barPercentage: 0.6,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: 'Total Durasi (Jam)',
                        data: <?php echo json_encode($stats['monthly_trend']['hours']); ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2.5,
                        fill: false,
                        tension: 0.3,
                        pointBackgroundColor: '#10b981',
                        pointRadius: 4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: getTextColor(), font: { size: 11, weight: '600' }, boxWidth: 12 }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: getTextColor(), font: { size: 11 } }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        grid: { color: getGridColor() },
                        ticks: { color: getTextColor(), stepSize: 1, font: { size: 11 } },
                        title: { display: true, text: 'Jumlah Rapat', color: getTextColor(), font: { size: 10 } }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        ticks: { color: '#10b981', font: { size: 11 } },
                        title: { display: true, text: 'Jam Rapat', color: '#10b981', font: { size: 10 } }
                    }
                }
            }
        });
    }

    // 2. Department Distribution Chart
    const deptCtx = document.getElementById('deptDistributionChart');
    let deptChart = null;
    if (deptCtx) {
        deptChart = new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($stats['dept_distribution']['labels']); ?>,
                datasets: [{
                    data: <?php echo json_encode($stats['dept_distribution']['data']); ?>,
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#14b8a6', '#64748b'],
                    borderWidth: 2,
                    borderColor: isDark() ? '#1e293b' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const val = context.raw || 0;
                                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                return ` ${context.label}: ${val} rapat (${pct}%)`;
                            }
                        }
                    }
                },
                cutout: '68%'
            }
        });
    }

    // 3. Room Usage Horizontal Bar Chart
    const roomCtx = document.getElementById('roomUsageChart');
    let roomChart = null;
    if (roomCtx) {
        <?php
            $roomLabels = [];
            $roomCounts = [];
            $roomDurations = [];
            foreach ($stats['room_stats'] as $rs) {
                $roomLabels[] = $rs['name'];
                $roomCounts[] = $rs['booking_count'];
                $roomDurations[] = $rs['total_hours'];
            }
        ?>
        roomChart = new Chart(roomCtx, {
            type: 'bar',
            indexAxis: 'y',
            data: {
                labels: <?php echo json_encode($roomLabels); ?>,
                datasets: [
                    {
                        label: 'Frekuensi Rapat',
                        data: <?php echo json_encode($roomCounts); ?>,
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                        barPercentage: 0.6
                    },
                    {
                        label: 'Total Jam',
                        data: <?php echo json_encode($roomDurations); ?>,
                        backgroundColor: '#6366f1',
                        borderRadius: 6,
                        barPercentage: 0.6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: getTextColor(), font: { size: 11, weight: '600' }, boxWidth: 12 }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: getGridColor() },
                        ticks: { color: getTextColor(), font: { size: 11 }, stepSize: 1 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: getTextColor(), font: { size: 11 } }
                    }
                }
            }
        });
    }

    // 4. Peak Hours Bar Chart
    const peakCtx = document.getElementById('peakHoursChart');
    let peakChart = null;
    if (peakCtx) {
        peakChart = new Chart(peakCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($stats['peak_hours']['labels']); ?>,
                datasets: [{
                    label: 'Intensitas Sesi Aktif',
                    data: <?php echo json_encode($stats['peak_hours']['data']); ?>,
                    backgroundColor: function(context) {
                        const val = context.raw || 0;
                        return val >= 3 ? '#ef4444' : (val >= 2 ? '#f59e0b' : '#3b82f6');
                    },
                    borderRadius: 6,
                    barPercentage: 0.7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: getTextColor(), font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: getGridColor() },
                        ticks: { color: getTextColor(), font: { size: 11 }, stepSize: 1 }
                    }
                }
            }
        });
    }

    // React to Dark Mode toggle events
    const observer = new MutationObserver(() => {
        const txtColor = getTextColor();
        const gridColor = getGridColor();

        [monthlyChart, roomChart, peakChart].forEach(chart => {
            if (chart) {
                if (chart.options.scales.x) {
                    chart.options.scales.x.ticks.color = txtColor;
                    if (chart.options.scales.x.grid) chart.options.scales.x.grid.color = gridColor;
                }
                if (chart.options.scales.y) {
                    chart.options.scales.y.ticks.color = txtColor;
                    if (chart.options.scales.y.grid) chart.options.scales.y.grid.color = gridColor;
                }
                if (chart.options.plugins.legend && chart.options.plugins.legend.labels) {
                    chart.options.plugins.legend.labels.color = txtColor;
                }
                chart.update();
            }
        });

        if (deptChart) {
            deptChart.data.datasets[0].borderColor = isDark() ? '#1e293b' : '#ffffff';
            deptChart.update();
        }
    });

    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
