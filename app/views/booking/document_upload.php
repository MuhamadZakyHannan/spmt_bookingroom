<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php
$hasDocument = !empty($booking['document_id']);
$statusLabels = [
    'pending' => 'Menunggu Persetujuan',
    'confirmed' => 'Terkonfirmasi',
];
$statusLabel = $statusLabels[$booking['status']] ?? ucfirst((string) $booking['status']);
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700/80 overflow-hidden">
        <header class="flex items-start justify-between gap-3 p-4 sm:p-8 border-b border-slate-100 dark:border-slate-700">
            <div class="flex items-start gap-3 min-w-0">
                <span class="flex w-11 h-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 dark:bg-violet-950/50 text-violet-600 dark:text-violet-300 text-lg">
                    <i class="fas fa-file-arrow-up"></i>
                </span>
                <div class="min-w-0">
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white"><?php echo $hasDocument ? 'Ganti' : 'Tambah'; ?> Surat Pendukung</h1>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">Dokumen dapat disusulkan tanpa mengubah jadwal atau status booking.</p>
                </div>
            </div>
            <a href="<?php echo htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8'); ?>" class="shrink-0 p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:text-slate-200 dark:hover:bg-slate-700" aria-label="Kembali">
                <i class="fas fa-times"></i>
            </a>
        </header>

        <div class="p-4 sm:p-8 space-y-6">
            <?php if ($error !== ''): ?>
                <div class="flex items-start gap-3 rounded-xl border border-rose-200 dark:border-rose-900 bg-rose-50 dark:bg-rose-950/40 p-4 text-sm text-rose-800 dark:text-rose-300">
                    <i class="fas fa-exclamation-triangle mt-0.5 text-rose-500"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <section class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/40 p-4 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Booking #<?php echo (int) $booking['id']; ?></p>
                        <h2 class="mt-1 font-bold text-slate-900 dark:text-white break-words"><?php echo htmlspecialchars($booking['title']); ?></h2>
                    </div>
                    <span class="rounded-lg border border-brand-200 dark:border-brand-800 bg-brand-50 dark:bg-brand-950/40 px-2.5 py-1 text-[10px] font-bold text-brand-700 dark:text-brand-300">
                        <?php echo htmlspecialchars($statusLabel); ?>
                    </span>
                </div>
                <dl class="mt-4 grid gap-3 text-xs text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                    <div class="flex items-start gap-2"><i class="fas fa-door-open mt-0.5 w-4 text-brand-500"></i><span><?php echo htmlspecialchars($booking['room_name']); ?></span></div>
                    <div class="flex items-start gap-2"><i class="fas fa-calendar mt-0.5 w-4 text-brand-500"></i><span><?php echo format_date($booking['date']); ?></span></div>
                    <div class="flex items-start gap-2 sm:col-span-2"><i class="fas fa-clock mt-0.5 w-4 text-brand-500"></i><span><?php echo format_time($booking['start_time']); ?>–<?php echo format_time($booking['end_time']); ?> WIB</span></div>
                </dl>
            </section>

            <?php if ($hasDocument): ?>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50/70 dark:bg-emerald-950/30 p-4">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Dokumen saat ini</p>
                        <p class="mt-1 truncate text-xs font-bold text-slate-800 dark:text-slate-100"><?php echo htmlspecialchars($booking['document_name']); ?></p>
                    </div>
                    <a href="booking_document.php?id=<?php echo (int) $booking['document_id']; ?>" target="_blank" rel="noopener" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-emerald-200 dark:border-emerald-800 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-bold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/40">
                        <i class="fas fa-eye"></i> Lihat Dokumen
                    </a>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" action="booking_document_upload.php" class="space-y-5" data-supporting-document-form>
                <?php echo csrf_field(); ?>
                <input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
                <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8'); ?>">

                <div>
                    <label for="supportingDocument" class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-2">Pilih Surat Pendukung <span class="text-rose-500">*</span></label>
                    <input type="file" name="supporting_document" id="supportingDocument" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="block w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-600 dark:text-slate-300 file:mr-4 file:border-0 file:border-r file:border-slate-200 dark:file:border-slate-700 file:bg-violet-50 dark:file:bg-violet-950/50 file:px-4 file:py-3 file:text-xs file:font-bold file:text-violet-700 dark:file:text-violet-300 hover:file:bg-violet-100 cursor-pointer">
                    <p class="mt-2 text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">Format PDF, JPG, atau PNG, maksimal 5 MB. File baru akan mengganti dokumen sebelumnya dan hanya dapat dibuka oleh pemilik booking serta Administrator.</p>
                </div>

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                    <a href="<?php echo htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-xl bg-slate-100 dark:bg-slate-700 px-5 py-2.5 text-center text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600">Batal</a>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 hover:bg-violet-700 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-violet-500/20">
                        <i class="fas fa-upload"></i> <?php echo $hasDocument ? 'Ganti' : 'Unggah'; ?> Surat Pendukung
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="public/js/booking-form.js?v=<?php echo asset_version('public/js/booking-form.js'); ?>"></script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
