<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-4xl mx-auto">
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

        <form method="POST" action="booking.php" class="space-y-6" data-booking-form data-availability-url="api/room_availability.php">
            <?php echo csrf_field(); ?>
            <?php
            $bookingFormPrefix = 'pageBooking';
            $bookingFormRooms = $rooms;
            $bookingFormSelectedRoomId = (int)($_POST['room_id'] ?? $selected_room_id);
            $bookingFormValues = [
                'user_name' => $user_name,
                'user_dept' => $user_dept,
                'title' => $title,
                'date' => $date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'activity_type' => $activity_type ?? 'internal_divisi',
                'attendees_count' => $attendees_count,
                'purpose' => $purpose
            ];
            require __DIR__ . '/_form_fields.php';
            ?>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                <a href="dashboard.php" class="py-2.5 px-5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-xl text-sm transition">Batal</a>
                <button type="submit" class="py-2.5 px-6 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-lg shadow-brand-500/20 transition text-sm flex items-center gap-2">
                    <i class="fas fa-check-circle"></i> Konfirmasi Booking
                </button>
            </div>
        </form>
    </div>
</div>

<script src="public/js/booking-form.js?v=<?php echo asset_version('public/js/booking-form.js'); ?>"></script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
