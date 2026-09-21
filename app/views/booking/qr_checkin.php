<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-lg overflow-hidden">
        <div class="p-6 sm:p-8 text-center border-b border-slate-100 dark:border-slate-700 bg-gradient-to-br from-blue-50 to-white dark:from-slate-900 dark:to-slate-800">
            <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center text-2xl <?php echo !empty($context['valid']) ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'; ?>">
                <i class="fas <?php echo !empty($context['valid']) ? 'fa-qrcode' : 'fa-times-circle'; ?>"></i>
            </div>
            <h1 class="mt-4 text-xl font-bold text-slate-900 dark:text-white">Check-in QR Ruang Rapat</h1>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Akun yang aktif harus sama dengan pemilik booking.</p>
        </div>

        <div class="p-6 sm:p-8">
            <?php if (!empty($context['valid'])): ?>
                <div class="space-y-4">
                    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm">
                        <i class="fas fa-shield-alt mr-1"></i>
                        QR valid dan belum digunakan.
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 sm:col-span-2">
                            <dt class="text-[10px] uppercase font-bold text-slate-400">Agenda</dt>
                            <dd class="font-bold text-slate-900 dark:text-white mt-0.5"><?php echo htmlspecialchars($context['title']); ?></dd>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                            <dt class="text-[10px] uppercase font-bold text-slate-400">Ruangan</dt>
                            <dd class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5"><?php echo htmlspecialchars($context['room_name']); ?></dd>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                            <dt class="text-[10px] uppercase font-bold text-slate-400">Jadwal</dt>
                            <dd class="font-mono font-semibold text-slate-800 dark:text-slate-200 mt-0.5"><?php echo format_time($context['start_time']); ?>–<?php echo format_time($context['end_time']); ?> WIB</dd>
                        </div>
                    </dl>

                    <form method="POST" action="qr_checkin.php" class="pt-2">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                            <i class="fas fa-sign-in-alt"></i> Konfirmasi Check-in
                        </button>
                    </form>

                    <p class="text-[11px] text-center text-slate-400">QR hanya berlaku singkat dan akan ditolak setelah dipakai.</p>
                </div>
            <?php else: ?>
                <div class="text-center space-y-4">
                    <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm">
                        <?php echo htmlspecialchars($context['message'] ?? 'QR tidak valid.'); ?>
                    </div>
                    <a href="my_bookings.php" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl font-bold text-xs transition">
                        <i class="fas fa-arrow-left"></i> Kembali ke Booking Saya
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
