<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700/80 p-6 sm:p-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-700 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-brand-50 dark:bg-brand-950/60 text-brand-600 dark:text-brand-400 rounded-xl flex items-center justify-center text-lg font-bold">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Form Pemesanan Ruangan</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Isi formulir di bawah ini untuk memesan ruang rapat.</p>
                </div>
            </div>
            <a href="dashboard.php" class="py-2 px-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-lg text-xs transition flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-4 mb-6 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-300 text-sm flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-rose-500 text-lg mt-0.5"></i>
                <div class="flex-1 leading-relaxed"><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="booking.php" class="space-y-6">
            <!-- User Information (Nama & Divisi) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Nama Pemesan / Penanggung Jawab *</label>
                    <div class="relative flex items-center">
                        <span class="absolute left-3.5 text-slate-400">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" name="user_name" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" placeholder="Masukkan nama pemesan / PIC..." value="<?php echo htmlspecialchars($user_name ?? ''); ?>" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Divisi / Departemen *</label>
                    <div class="relative flex items-center">
                        <span class="absolute left-3.5 text-slate-400 pointer-events-none">
                            <i class="fas fa-sitemap"></i>
                        </span>
                        <select name="user_dept" class="w-full pl-10 pr-8 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition cursor-pointer appearance-none" required>
                            <option value="">-- Pilih Divisi / Departemen --</option>
                            <?php 
                            $departments = [
                                'SPMT - Pendukung Operasi',
                                'SPMT - Teknik & IT',
                                'SPMT - Rendal OPS',
                                'SPMT - Integrated PNC',
                                'SPMT - Operasional',
                                'SPMT - Ruang Rapat dan Branch Manager',
                                'SPMT - SPJM',
                                'Subreg - Keuangan',
                                'Subreg - Teknik',
                                'Subreg - Integraterd PNC',
                                'Subreg - Komersial',
                                'Subreg - Arsip'
                            ];
                            foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo (isset($user_dept) && $user_dept === $dept) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="absolute right-3.5 text-slate-400 pointer-events-none text-xs">
                            <i class="fas fa-chevron-down"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Room Selection -->
            <div>
                <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Pilih Ruangan Rapat *</label>
                <select name="room_id" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" required>
                    <option value="">-- Pilih Ruangan --</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php echo ($selected_room_id === $r['id'] || (isset($_POST['room_id']) && $_POST['room_id'] == $r['id'])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['name']); ?> (Kapasitas: <?php echo $r['capacity']; ?> Orang - <?php echo htmlspecialchars($r['location']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tingkat Kepentingan Kegiatan (Kriteria K1 SAW) -->
            <div>
                <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                    Tingkat Kepentingan Kegiatan (Kriteria K1 SAW) *
                </label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 text-slate-400 pointer-events-none">
                        <i class="fas fa-layer-group"></i>
                    </span>
                    <select name="activity_type" class="w-full pl-10 pr-8 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition cursor-pointer appearance-none" required>
                        <option value="direksi_eksternal" <?php echo ($activity_type ?? '') === 'direksi_eksternal' ? 'selected' : ''; ?>>
                            🌟 Rapat dengan Direksi / Pihak Eksternal (Prioritas Tertinggi - Bobot 5)
                        </option>
                        <option value="antar_divisi" <?php echo ($activity_type ?? '') === 'antar_divisi' ? 'selected' : ''; ?>>
                            🔷 Rapat Koordinasi Antar Divisi (Bobot 4)
                        </option>
                        <option value="internal_divisi" <?php echo ($activity_type ?? 'internal_divisi') === 'internal_divisi' ? 'selected' : ''; ?>>
                            🔹 Rapat Internal Divisi (Bobot 3)
                        </option>
                        <option value="pelatihan" <?php echo ($activity_type ?? '') === 'pelatihan' ? 'selected' : ''; ?>>
                            🔸 Sosialisasi / Pelatihan (Bobot 2)
                        </option>
                        <option value="rutin" <?php echo ($activity_type ?? '') === 'rutin' ? 'selected' : ''; ?>>
                            ▫️ Kegiatan Rutin / Non-Prioritas (Bobot 1)
                        </option>
                    </select>
                    <span class="absolute right-3.5 text-slate-400 pointer-events-none text-xs">
                        <i class="fas fa-chevron-down"></i>
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                    * Kategori ini digunakan oleh Sistem Pendukung Keputusan (Metode SAW) jika terjadi bentrok jadwal pada ruangan dan waktu yang sama.
                </p>
            </div>

            <!-- Meeting Title -->
            <div>
                <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Judul Meeting / Agenda *</label>
                <input type="text" name="title" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" placeholder="Contoh: Evaluasi Strategy Q3" value="<?php echo htmlspecialchars($title); ?>" required>
            </div>

            <!-- Date and Time Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Tanggal Meeting *</label>
                    <input type="date" name="date" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" value="<?php echo htmlspecialchars($date); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Jam Mulai *</label>
                    <input type="time" name="start_time" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" value="<?php echo htmlspecialchars($start_time); ?>" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Jam Selesai *</label>
                    <input type="time" name="end_time" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" value="<?php echo htmlspecialchars($end_time); ?>" required>
                </div>
            </div>

            <!-- Attendees Count -->
            <div>
                <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Estimasi Jumlah Peserta *</label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 text-slate-400">
                        <i class="fas fa-users"></i>
                    </span>
                    <input type="number" name="attendees_count" class="w-full pl-10 pr-16 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" min="1" max="100" value="<?php echo (int)$attendees_count; ?>" required>
                    <span class="absolute right-3.5 text-slate-400 text-xs font-semibold">Orang</span>
                </div>
            </div>

            <!-- Purpose / Notes -->
            <div>
                <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Tujuan / Catatan Tambahan</label>
                <textarea name="purpose" rows="3" class="w-full p-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" placeholder="Jelaskan secara singkat tujuan atau kebutuhan khusus (misal: perlu tambahan mikrofon)..."><?php echo htmlspecialchars($purpose); ?></textarea>
            </div>

            <!-- Notice SPK SAW -->
            <div class="p-4 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800/60 text-sky-900 dark:text-sky-300 text-xs flex items-start gap-3">
                <i class="fas fa-info-circle text-sky-600 dark:text-sky-400 text-base mt-0.5 shrink-0"></i>
                <div class="leading-relaxed">
                    <strong>Integrasi SPK SAW:</strong> Jika jadwal yang Anda pilih bersamaan dengan agenda lain, pengajuan tetap akan diterima ke sistem (status <em>Pending</em>). Sistem pendukung keputusan (metode SAW) akan menghitung pembobotan (K1 Kepentingan, K2 Peserta, K3 Durasi, K4 Waktu Pengajuan, K5 Frekuensi Divisi) untuk memberikan rekomendasi prioritas terbaik kepada Administrator.
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <a href="dashboard.php" class="py-2.5 px-5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-xl text-sm transition">Batal</a>
                <button type="submit" class="py-2.5 px-6 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-lg shadow-brand-500/20 transition text-sm flex items-center gap-2">
                    <i class="fas fa-check-circle"></i> Konfirmasi Booking
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
