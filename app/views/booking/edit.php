<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php
$isConfirmed = ($booking['status'] ?? '') === 'confirmed';
$statusLabel = $isConfirmed ? 'Terkonfirmasi' : 'Menunggu Persetujuan';
$statusClass = $isConfirmed
    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800'
    : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800';
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700/80 p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-700 mb-6">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 bg-brand-50 dark:bg-brand-950/60 text-brand-600 dark:text-brand-400 rounded-xl flex items-center justify-center text-lg font-bold shrink-0">
                    <i class="fas fa-pen-to-square"></i>
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Edit Booking #<?php echo (int) $booking['id']; ?></h1>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg border text-[10px] font-bold uppercase <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars($statusLabel); ?>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Perubahan jadwal akan diperiksa ulang terhadap kapasitas dan bentrok ruangan.</p>
                </div>
            </div>
            <a href="<?php echo htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8'); ?>" class="self-start sm:self-auto py-2 px-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-lg text-xs transition flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-4 mb-6 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-300 text-sm flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-rose-500 text-lg mt-0.5"></i>
                <div class="flex-1 leading-relaxed"><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <form
            method="POST"
            enctype="multipart/form-data"
            action="edit_booking.php?id=<?php echo (int) $booking['id']; ?>"
            class="space-y-6"
            data-booking-form
            data-availability-url="api/room_availability.php"
            data-exclude-booking-id="<?php echo (int) $booking['id']; ?>"
        >
            <?php echo csrf_field(); ?>
            <input type="hidden" name="booking_id" value="<?php echo (int) $booking['id']; ?>">
            <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8'); ?>">
            <?php
            $bookingFormPrefix = 'editBooking';
            $bookingFormRooms = $rooms;
            $bookingFormSelectedRoomId = (int) $values['room_id'];
            $bookingFormValues = $values;
            $bookingFormCurrentDocument = $current_document;
            $bookingFormInfoText = $isConfirmed
                ? 'Booking ini sudah terkonfirmasi. Hanya Administrator yang dapat menyimpan perubahan, dan bentrok dengan jadwal terkonfirmasi lain tetap ditolak.'
                : 'Status tetap menunggu persetujuan setelah diedit. Jika beririsan dengan pengajuan lain, Administrator akan meninjau dan menentukan prioritasnya.';
            require __DIR__ . '/_form_fields.php';
            ?>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <a href="<?php echo htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8'); ?>" class="py-2.5 px-5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-xl text-sm transition text-center">Batal</a>
                <button type="submit" class="py-2.5 px-6 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-lg shadow-brand-500/20 transition text-sm flex items-center justify-center gap-2">
                    <i class="fas fa-floppy-disk"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script src="public/js/booking-form.js?v=<?php echo asset_version('public/js/booking-form.js'); ?>"></script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
