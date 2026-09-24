<!-- TAB 1: KONFLIK JADWAL & REKOMENDASI SAW -->
<div id="tabContentConflicts" class="<?php echo $hasConflicts ? '' : 'hidden'; ?> space-y-6 mb-8">
    <?php if (!$hasConflicts): ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 p-12 text-center shadow-sm">
            <div class="w-16 h-16 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-500 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-4 border border-emerald-200 dark:border-emerald-800 shadow-inner">
                <i class="fas fa-check-double"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Tidak Ada Konflik Jadwal</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1">
                Seluruh pengajuan pemesanan berada pada slot ruangan dan waktu yang berbeda. Anda dapat mengelola dan menyetujui pemesanan secara langsung di tab Semua Pemesanan.
            </p>
            <button type="button" onclick="switchBookingTab('all')" class="mt-5 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold transition inline-flex items-center gap-2 shadow-sm">
                <i class="fas fa-list"></i> Buka Tab Semua Pemesanan
            </button>
        </div>
    <?php else: ?>
        <!-- Panduan & Legend SPK SAW -->
        <div class="bg-gradient-to-r from-amber-500/10 via-brand-500/10 to-blue-500/10 dark:from-amber-950/30 dark:via-brand-950/20 dark:to-blue-950/30 border border-amber-200/80 dark:border-amber-700/50 rounded-2xl p-4 sm:p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-amber-500 text-white rounded-xl flex items-center justify-center text-lg font-bold shadow-md shadow-amber-500/20 shrink-0">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>Sistem Pendukung Keputusan (SPK) - Metode SAW</span>
                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 bg-amber-500 text-white rounded-md">Otomatis</span>
                        </h2>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                            Ketika beberapa pengajuan memperebutkan ruangan dan jam yang sama, metode SAW secara objektif menghitung <strong>5 kriteria pembobotan</strong> untuk menentukan pemesan prioritas tertinggi.
                        </p>
                    </div>
                </div>
                <button type="button" onclick="toggleSawGuide()" class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold transition flex items-center gap-1.5 shrink-0 shadow-xs cursor-pointer">
                    <i class="fas fa-info-circle text-amber-500"></i>
                    <span id="sawGuideBtnText">Lihat Kriteria & Bobot</span>
                </button>
            </div>

            <!-- Collapsible Detail Bobot Kriteria -->
            <div id="sawGuideContent" class="hidden mt-4 pt-4 border-t border-amber-200/60 dark:border-amber-800/40 text-xs text-slate-700 dark:text-slate-300">
                <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                    <div class="p-3 bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200 dark:border-slate-700">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">K1 (Benefit - 30%)</div>
                        <div class="font-bold text-slate-900 dark:text-white mt-1">Kepentingan Kegiatan</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Direksi/Eksternal (5), Antar Divisi (4), Internal Divisi (3), Pelatihan (2), Rutin (1)</div>
                    </div>
                    <div class="p-3 bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200 dark:border-slate-700">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">K2 (Benefit - 20%)</div>
                        <div class="font-bold text-slate-900 dark:text-white mt-1">Jumlah Peserta</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">&gt; 20 org (4), 11–20 org (3), 5–10 org (2), &lt; 5 org (1)</div>
                    </div>
                    <div class="p-3 bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200 dark:border-slate-700">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">K3 (Cost - 15%)</div>
                        <div class="font-bold text-slate-900 dark:text-white mt-1">Durasi Penggunaan</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">1 jam (1), 2 jam (2), 3 jam (3), &ge; 4 jam (4) [Durasi ringkas lebih diprioritaskan]</div>
                    </div>
                    <div class="p-3 bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200 dark:border-slate-700">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">K4 (Benefit - 20%)</div>
                        <div class="font-bold text-slate-900 dark:text-white mt-1">Waktu Pengajuan</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Diajukan &ge; H-3 (3), H-1 s.d H-2 (2), Hari yang sama (1)</div>
                    </div>
                    <div class="p-3 bg-white dark:bg-slate-800/90 rounded-xl border border-slate-200 dark:border-slate-700">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">K5 (Cost - 15%)</div>
                        <div class="font-bold text-slate-900 dark:text-white mt-1">Frekuensi Divisi</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Jarang 0-2x (3), Cukup sering 3-5x (2), Sering &gt;5x (1) bulan ini</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Kasus Konflik Jadwal -->
        <?php foreach ($conflict_analyses as $caIndex => $ca): ?>
            <?php 
                $winner = $ca['saw']['winner'];
                $loserIds = [];
                foreach ($ca['saw']['results'] as $res) {
                    if ($res['booking_id'] != $winner['booking_id']) {
                        $loserIds[] = $res['booking_id'];
                    }
                }
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border-2 border-amber-400/80 dark:border-amber-600/70 shadow-lg overflow-hidden transition">
                <!-- Group Header -->
                <div class="p-4 sm:p-5 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-950/40 dark:to-orange-950/30 border-b border-amber-200 dark:border-amber-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-black text-sm shadow">
                            #<?php echo $caIndex + 1; ?>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                    <?php echo htmlspecialchars($ca['room_name']); ?>
                                </h3>
                                <span class="px-2 py-0.5 bg-brand-600 text-white font-mono text-[11px] font-semibold rounded-md">
                                    <?php echo htmlspecialchars($ca['room_code']); ?>
                                </span>
                            </div>
                            <div class="text-xs text-slate-600 dark:text-slate-300 mt-0.5 flex items-center gap-2">
                                <span><i class="fas fa-calendar-day text-amber-600 dark:text-amber-400"></i> <?php echo format_date($ca['date']); ?></span>
                                <span>•</span>
                                <span class="text-rose-600 dark:text-rose-400 font-semibold">
                                    <i class="fas fa-exclamation-triangle"></i> Terdeteksi <?php echo count($ca['bookings']); ?> Jadwal Bersamaan
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700 text-xs font-bold rounded-xl shadow-xs">
                            Memerlukan Resolusi Konflik
                        </span>
                    </div>
                </div>

                <!-- Matrix Calculation Table -->
                <div class="responsive-table-shell">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-100/90 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 uppercase font-bold text-[11px] tracking-wider">
                                <th class="py-3 px-3 text-center">Alternatif</th>
                                <th class="py-3 px-3">Agenda & Pemesan</th>
                                <th class="py-3 px-2 text-center">Jam</th>
                                <th class="py-3 px-2 text-center" title="K1 (Benefit 30%)">K1 (30%)<br><span class="text-[9px] font-normal lowercase">urgensi</span></th>
                                <th class="py-3 px-2 text-center" title="K2 (Benefit 20%)">K2 (20%)<br><span class="text-[9px] font-normal lowercase">peserta</span></th>
                                <th class="py-3 px-2 text-center" title="K3 (Cost 15%)">K3 (15%)<br><span class="text-[9px] font-normal lowercase">durasi</span></th>
                                <th class="py-3 px-2 text-center" title="K4 (Benefit 20%)">K4 (20%)<br><span class="text-[9px] font-normal lowercase">pengajuan</span></th>
                                <th class="py-3 px-2 text-center" title="K5 (Cost 15%)">K5 (15%)<br><span class="text-[9px] font-normal lowercase">frek.divisi</span></th>
                                <th class="py-3 px-3 text-center">Skor Akhir (V)</th>
                                <th class="py-3 px-3 text-center">Peringkat</th>
                                <th class="py-3 px-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/80">
                            <?php foreach ($ca['saw']['results'] as $altCode => $alt): ?>
                                <?php 
                                    $isWinner = ($alt['rank'] === 1); 
                                    $b = $alt['booking'];
                                ?>
                                <tr class="<?php echo $isWinner ? 'bg-emerald-50/70 dark:bg-emerald-950/30 font-medium' : 'hover:bg-slate-50 dark:hover:bg-slate-700/30'; ?> transition">
                                    <!-- Alternatif Code -->
                                    <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg text-xs font-black <?php echo $isWinner ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200'; ?>">
                                            <?php echo $alt['code']; ?>
                                        </span>
                                    </td>

                                    <!-- Agenda & Pemesan -->
                                    <td class="py-3.5 px-3">
                                        <div class="font-bold text-slate-900 dark:text-white text-xs">
                                            <?php echo htmlspecialchars($b['title']); ?>
                                        </div>
                                        <div class="text-[11px] text-slate-600 dark:text-slate-300 mt-0.5">
                                            <strong><?php echo htmlspecialchars($b['user_name']); ?></strong> • <?php echo htmlspecialchars($b['user_dept'] ?? '-'); ?>
                                        </div>
                                        <?php if (!empty($b['document_id'])): ?>
                                            <a href="booking_document.php?id=<?php echo (int) $b['document_id']; ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 mt-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-violet-50 dark:bg-violet-950/50 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 transition" title="<?php echo htmlspecialchars($b['document_name']); ?>">
                                                <i class="fas fa-file-lines"></i> Surat Pendukung
                                            </a>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Jam -->
                                    <td class="py-3.5 px-2 text-center whitespace-nowrap font-mono text-[11px]">
                                        <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-900 rounded font-bold">
                                            <?php echo format_time($b['start_time']); ?> - <?php echo format_time($b['end_time']); ?>
                                        </span>
                                    </td>

                                    <!-- K1: Kepentingan -->
                                    <td class="py-3.5 px-2 text-center whitespace-nowrap">
                                        <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                            X = <?php echo $alt['raw_scores']['k1']; ?>
                                        </div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 truncate max-w-[110px]" title="<?php echo htmlspecialchars($alt['raw_labels']['k1']); ?>">
                                            R = <?php echo number_format($alt['normalized']['k1'], 2); ?>
                                        </div>
                                    </td>

                                    <!-- K2: Peserta -->
                                    <td class="py-3.5 px-2 text-center whitespace-nowrap">
                                        <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                            X = <?php echo $alt['raw_scores']['k2']; ?>
                                        </div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400" title="<?php echo htmlspecialchars($alt['raw_labels']['k2']); ?>">
                                            R = <?php echo number_format($alt['normalized']['k2'], 2); ?>
                                        </div>
                                    </td>

                                    <!-- K3: Durasi -->
                                    <td class="py-3.5 px-2 text-center whitespace-nowrap">
                                        <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                            X = <?php echo $alt['raw_scores']['k3']; ?>
                                        </div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400" title="<?php echo htmlspecialchars($alt['raw_labels']['k3']); ?>">
                                            R = <?php echo number_format($alt['normalized']['k3'], 2); ?>
                                        </div>
                                    </td>

                                    <!-- K4: Pengajuan -->
                                    <td class="py-3.5 px-2 text-center whitespace-nowrap">
                                        <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                            X = <?php echo $alt['raw_scores']['k4']; ?>
                                        </div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400" title="<?php echo htmlspecialchars($alt['raw_labels']['k4']); ?>">
                                            R = <?php echo number_format($alt['normalized']['k4'], 2); ?>
                                        </div>
                                    </td>

                                    <!-- K5: Frekuensi Divisi -->
                                    <td class="py-3.5 px-2 text-center whitespace-nowrap">
                                        <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                            X = <?php echo $alt['raw_scores']['k5']; ?>
                                        </div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400" title="<?php echo htmlspecialchars($alt['raw_labels']['k5']); ?>">
                                            R = <?php echo number_format($alt['normalized']['k5'], 2); ?>
                                        </div>
                                    </td>

                                    <!-- Skor Akhir (V) -->
                                    <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                        <div class="text-sm font-black <?php echo $isWinner ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-800 dark:text-slate-200'; ?>">
                                            <?php echo number_format($alt['preference_score'], 4); ?>
                                        </div>
                                        <div class="w-16 bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden mx-auto mt-1">
                                            <div class="data-progress-bar <?php echo $isWinner ? 'bg-emerald-500' : 'bg-slate-400'; ?> h-full rounded-full" style="--progress-value: <?php echo min(100, $alt['preference_score'] * 100); ?>%"></div>
                                        </div>
                                    </td>

                                    <!-- Peringkat -->
                                    <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                        <?php if ($isWinner): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200 text-[11px] font-black rounded-lg border border-emerald-300 dark:border-emerald-700 shadow-xs animate-pulse">
                                                <i class="fas fa-crown text-amber-500"></i> Rank 1 (Prioritas)
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-[11px] font-bold rounded-lg">
                                                Rank <?php echo $alt['rank']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Aksi Manual -->
                                    <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1">
                                            <!-- Setujui Single -->
                                            <form method="POST" action="admin_bookings.php" class="inline-block" onsubmit="return confirm('Pilih dan setujui alternatif <?php echo $alt['code']; ?>?')">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                <input type="hidden" name="status" value="confirmed">
                                                <button type="submit" class="p-1.5 px-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition text-xs shadow-xs cursor-pointer" title="Setujui Alternatif Ini">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>

                                            <!-- Tolak Single -->
                                            <form method="POST" action="admin_bookings.php" class="inline-block" onsubmit="return confirm('Tolak dan batalkan pengajuan <?php echo $alt['code']; ?>?')">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                <button type="submit" class="p-1.5 px-2 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold rounded-lg transition text-xs cursor-pointer" title="Tolak">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Winner Decision Action Bar -->
                <div class="p-4 sm:p-5 bg-gradient-to-r from-emerald-500/10 via-teal-500/10 to-emerald-500/10 dark:from-emerald-950/40 dark:via-teal-950/30 dark:to-emerald-950/40 border-t border-emerald-200 dark:border-emerald-800 flex flex-col xl:flex-row items-stretch xl:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-600 text-white rounded-xl flex items-center justify-center text-lg font-bold shadow-md shadow-emerald-600/30 shrink-0">
                            <i class="fas fa-award"></i>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-emerald-950 dark:text-emerald-200 flex items-center gap-1.5">
                                <span>Rekomendasi Keputusan SAW:</span>
                                <span class="px-2 py-0.5 bg-emerald-600 text-white rounded text-[10px] font-black uppercase">Ranking 1</span>
                            </div>
                            <div class="text-xs text-slate-700 dark:text-slate-300 mt-0.5">
                                Setujui alternatif <strong><?php echo $winner['code']; ?> (<?php echo htmlspecialchars($winner['booking']['title']); ?>)</strong> oleh <strong><?php echo htmlspecialchars($winner['booking']['user_name']); ?></strong> dengan skor preferensi <strong><?php echo number_format($winner['preference_score'], 4); ?></strong>.
                            </div>
                        </div>
                    </div>

                    <?php 
                        $defaultRejectionReason = "Pengajuan ditolak karena ada agenda " . $winner['booking']['title'];
                    ?>
                    <form method="POST" action="admin_bookings.php" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full xl:w-auto" onsubmit="return confirm('Terapkan keputusan rekomendasi SAW?\n\n- Alternatif <?php echo $winner['code']; ?> (<?php echo addslashes($winner['booking']['title']); ?>) akan DISETUJUI (Confirmed).\n- Jadwal bentrok lainnya akan DIBATALKAN otomatis dengan catatan alasan penolakan.')">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="apply_saw_decision">
                        <input type="hidden" name="winner_id" value="<?php echo $winner['booking_id']; ?>">
                        <input type="hidden" name="loser_ids" value="<?php echo implode(',', $loserIds); ?>">
                        <div class="relative flex-grow sm:w-80">
                            <input 
                                type="text" 
                                name="rejection_reason" 
                                value="<?php echo htmlspecialchars($defaultRejectionReason); ?>" 
                                placeholder="Alasan penolakan pengajuan lain..." 
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-sm"
                                title="Catatan alasan ini akan dikirimkan kepada pemohon jadwal lain yang tidak terpilih"
                            >
                        </div>
                        <button type="submit" class="shrink-0 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-lg shadow-emerald-600/25 transition flex items-center justify-center gap-2 cursor-pointer">
                            <i class="fas fa-check-double"></i>
                            <span>Terapkan Rekomendasi (Setujui <?php echo $winner['code']; ?>)</span>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
