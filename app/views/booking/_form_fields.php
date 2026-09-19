<?php
$bookingFormPrefix = $bookingFormPrefix ?? 'booking';
$bookingFormRooms = $bookingFormRooms ?? [];
$bookingFormSelectedRoomId = (int)($bookingFormSelectedRoomId ?? 0);
$bookingFormValues = array_merge([
    'user_name' => $_SESSION['user_name'] ?? '',
    'user_dept' => '',
    'title' => '',
    'date' => date('Y-m-d'),
    'start_time' => '09:00',
    'end_time' => '10:00',
    'activity_type' => 'internal_divisi',
    'attendees_count' => 1,
    'purpose' => ''
], $bookingFormValues ?? []);

$bookingFormDepartments = [
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

$bookingFormActivityTypes = $activity_types ?? SawService::ACTIVITY_TYPES;
$bookingFormStartTime = substr((string)$bookingFormValues['start_time'], 0, 5);
$bookingFormEndTime = substr((string)$bookingFormValues['end_time'], 0, 5);
?>

<div class="space-y-5">
    <section class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/40 p-4 sm:p-5">
        <div class="flex items-center gap-2 mb-4">
            <span class="w-7 h-7 rounded-lg bg-brand-100 dark:bg-brand-900/50 text-brand-700 dark:text-brand-300 flex items-center justify-center text-xs font-bold">1</span>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Informasi Pemesan</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Data penanggung jawab kegiatan.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="<?php echo $bookingFormPrefix; ?>UserName" class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Nama Pemesan / PIC <span class="text-rose-500">*</span></label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 z-10 text-slate-400 dark:text-slate-300 pointer-events-none"><i class="fas fa-user"></i></span>
                    <input type="text" name="user_name" id="<?php echo $bookingFormPrefix; ?>UserName" autocomplete="name" class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" placeholder="Nama penanggung jawab" value="<?php echo htmlspecialchars($bookingFormValues['user_name']); ?>" required>
                </div>
            </div>

            <div>
                <label for="<?php echo $bookingFormPrefix; ?>UserDept" class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Divisi / Departemen <span class="text-rose-500">*</span></label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 z-10 text-slate-400 dark:text-slate-300 pointer-events-none"><i class="fas fa-sitemap"></i></span>
                    <select name="user_dept" id="<?php echo $bookingFormPrefix; ?>UserDept" class="w-full pl-10 pr-8 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition cursor-pointer appearance-none" required>
                        <option value="">Pilih divisi / departemen</option>
                        <?php foreach ($bookingFormDepartments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $bookingFormValues['user_dept'] === $dept ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="absolute right-3.5 z-10 text-slate-400 dark:text-slate-300 pointer-events-none text-xs"><i class="fas fa-chevron-down"></i></span>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/40 p-4 sm:p-5">
        <div class="flex items-center gap-2 mb-4">
            <span class="w-7 h-7 rounded-lg bg-brand-100 dark:bg-brand-900/50 text-brand-700 dark:text-brand-300 flex items-center justify-center text-xs font-bold">2</span>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Ruangan dan Jadwal</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Semua jam menggunakan Waktu Indonesia Barat (WIB/UTC+7).</p>
            </div>
        </div>

        <div>
            <label for="<?php echo $bookingFormPrefix; ?>RoomId" class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Ruangan Rapat <span class="text-rose-500">*</span></label>
            <select name="room_id" id="<?php echo $bookingFormPrefix; ?>RoomId" data-booking-room class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" required>
                <option value="">Pilih ruangan</option>
                <?php foreach ($bookingFormRooms as $room): ?>
                    <option
                        value="<?php echo (int)$room['id']; ?>"
                        data-name="<?php echo htmlspecialchars($room['name'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-capacity="<?php echo (int)$room['capacity']; ?>"
                        data-location="<?php echo htmlspecialchars($room['location'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>"
                        data-facilities="<?php echo htmlspecialchars($room['facilities'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $bookingFormSelectedRoomId === (int)$room['id'] ? 'selected' : ''; ?>
                    ><?php echo htmlspecialchars($room['name']); ?> — <?php echo (int)$room['capacity']; ?> orang, <?php echo htmlspecialchars($room['location'] ?? '-'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div data-booking-room-info class="hidden mt-3 rounded-xl border border-brand-200 dark:border-brand-800 bg-brand-50/70 dark:bg-brand-950/30 p-3 text-xs text-slate-700 dark:text-slate-300">
            <div class="flex flex-wrap gap-x-5 gap-y-2">
                <span><i class="fas fa-users text-brand-600 dark:text-brand-400 mr-1.5"></i><strong data-room-capacity></strong></span>
                <span><i class="fas fa-location-dot text-brand-600 dark:text-brand-400 mr-1.5"></i><span data-room-location></span></span>
                <span><i class="fas fa-list-check text-brand-600 dark:text-brand-400 mr-1.5"></i><span data-room-facilities></span></span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
            <div>
                <label for="<?php echo $bookingFormPrefix; ?>Date" class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Tanggal <span class="text-rose-500">*</span></label>
                <input type="date" name="date" id="<?php echo $bookingFormPrefix; ?>Date" data-booking-date class="booking-date-input w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" value="<?php echo htmlspecialchars($bookingFormValues['date']); ?>" min="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div>
                <label for="<?php echo $bookingFormPrefix; ?>StartTime" class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Jam Mulai (WIB) <span class="text-rose-500">*</span></label>
                <div class="relative" data-time-picker>
                    <input type="hidden" name="start_time" id="<?php echo $bookingFormPrefix; ?>StartTime" data-booking-start-time value="<?php echo htmlspecialchars($bookingFormStartTime); ?>">
                    <button type="button" data-time-picker-trigger class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm font-semibold tabular-nums focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition flex items-center justify-between" aria-haspopup="dialog" aria-expanded="false">
                        <span data-time-picker-value><?php echo htmlspecialchars($bookingFormStartTime); ?></span>
                        <i class="far fa-clock text-slate-500 dark:text-slate-200"></i>
                    </button>
                    <div data-time-picker-panel role="dialog" aria-label="Pilih jam mulai dalam format 24 jam" class="hidden absolute left-0 right-0 sm:right-auto sm:w-72 mt-2 z-30 rounded-2xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 p-4 shadow-2xl">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200 mb-3">Pilih Jam Mulai (WIB)</p>
                        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                            <select data-time-hour class="w-full px-3 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-white font-bold tabular-nums focus:outline-none focus:ring-2 focus:ring-brand-500">
                                <?php for ($hour = 0; $hour < 24; $hour++): $hourValue = sprintf('%02d', $hour); ?>
                                    <option value="<?php echo $hourValue; ?>"><?php echo $hourValue; ?></option>
                                <?php endfor; ?>
                            </select>
                            <span class="font-black text-slate-500 dark:text-slate-300">:</span>
                            <select data-time-minute class="w-full px-3 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-white font-bold tabular-nums focus:outline-none focus:ring-2 focus:ring-brand-500">
                                <?php for ($minute = 0; $minute < 60; $minute++): $minuteValue = sprintf('%02d', $minute); ?>
                                    <option value="<?php echo $minuteValue; ?>"><?php echo $minuteValue; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-slate-100 dark:border-slate-700">
                            <button type="button" data-time-picker-close class="px-3 py-2 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Batal</button>
                            <button type="button" data-time-picker-apply class="px-3 py-2 rounded-lg text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white">Pilih Waktu</button>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="<?php echo $bookingFormPrefix; ?>EndTime" class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Jam Selesai (WIB) <span class="text-rose-500">*</span></label>
                <div class="relative" data-time-picker>
                    <input type="hidden" name="end_time" id="<?php echo $bookingFormPrefix; ?>EndTime" data-booking-end-time value="<?php echo htmlspecialchars($bookingFormEndTime); ?>">
                    <button type="button" data-time-picker-trigger class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm font-semibold tabular-nums focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition flex items-center justify-between" aria-haspopup="dialog" aria-expanded="false">
                        <span data-time-picker-value><?php echo htmlspecialchars($bookingFormEndTime); ?></span>
                        <i class="far fa-clock text-slate-500 dark:text-slate-200"></i>
                    </button>
                    <div data-time-picker-panel role="dialog" aria-label="Pilih jam selesai dalam format 24 jam" class="hidden absolute left-0 right-0 sm:right-auto sm:w-72 mt-2 z-30 rounded-2xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 p-4 shadow-2xl">
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200 mb-3">Pilih Jam Selesai (WIB)</p>
                        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                            <select data-time-hour class="w-full px-3 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-white font-bold tabular-nums focus:outline-none focus:ring-2 focus:ring-brand-500">
                                <?php for ($hour = 0; $hour < 24; $hour++): $hourValue = sprintf('%02d', $hour); ?>
                                    <option value="<?php echo $hourValue; ?>"><?php echo $hourValue; ?></option>
                                <?php endfor; ?>
                            </select>
                            <span class="font-black text-slate-500 dark:text-slate-300">:</span>
                            <select data-time-minute class="w-full px-3 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-white font-bold tabular-nums focus:outline-none focus:ring-2 focus:ring-brand-500">
                                <?php for ($minute = 0; $minute < 60; $minute++): $minuteValue = sprintf('%02d', $minute); ?>
                                    <option value="<?php echo $minuteValue; ?>"><?php echo $minuteValue; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-slate-100 dark:border-slate-700">
                            <button type="button" data-time-picker-close class="px-3 py-2 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">Batal</button>
                            <button type="button" data-time-picker-apply class="px-3 py-2 rounded-lg text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white">Pilih Waktu</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
            <p data-booking-schedule-error class="hidden text-rose-600 dark:text-rose-400 font-semibold"></p>
            <p data-booking-duration class="sm:ml-auto inline-flex items-center gap-1.5 text-brand-700 dark:text-brand-300 font-bold">
                <i class="fas fa-clock"></i><span>Durasi: 1 jam</span>
            </p>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/40 p-4 sm:p-5">
        <div class="flex items-center gap-2 mb-4">
            <span class="w-7 h-7 rounded-lg bg-brand-100 dark:bg-brand-900/50 text-brand-700 dark:text-brand-300 flex items-center justify-center text-xs font-bold">3</span>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Detail Kegiatan</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Informasi agenda dan kebutuhan rapat.</p>
            </div>
        </div>

        <div>
            <label for="<?php echo $bookingFormPrefix; ?>Title" class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Judul Meeting / Agenda <span class="text-rose-500">*</span></label>
            <input type="text" name="title" id="<?php echo $bookingFormPrefix; ?>Title" class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" placeholder="Contoh: Evaluasi strategi triwulan III" value="<?php echo htmlspecialchars($bookingFormValues['title']); ?>" required>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label for="<?php echo $bookingFormPrefix; ?>ActivityType" class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Jenis Kegiatan <span class="text-rose-500">*</span></label>
                <select name="activity_type" id="<?php echo $bookingFormPrefix; ?>ActivityType" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" required>
                    <?php foreach ($bookingFormActivityTypes as $activityKey => $activityData): ?>
                        <option value="<?php echo htmlspecialchars($activityKey); ?>" <?php echo $bookingFormValues['activity_type'] === $activityKey ? 'selected' : ''; ?>><?php echo htmlspecialchars($activityData['label']); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5">Digunakan sebagai salah satu kriteria SAW jika jadwal bentrok.</p>
            </div>

            <div>
                <label for="<?php echo $bookingFormPrefix; ?>Attendees" class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Jumlah Peserta <span class="text-rose-500">*</span></label>
                <div class="relative flex items-center">
                    <span class="absolute left-3.5 z-10 text-slate-400 dark:text-slate-300 pointer-events-none"><i class="fas fa-users"></i></span>
                    <input type="number" name="attendees_count" id="<?php echo $bookingFormPrefix; ?>Attendees" data-booking-attendees class="w-full pl-10 pr-16 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition" min="1" max="100" value="<?php echo max(1, (int)$bookingFormValues['attendees_count']); ?>" required>
                    <span class="absolute right-3.5 z-10 text-slate-400 dark:text-slate-300 text-xs font-semibold pointer-events-none">Orang</span>
                </div>
                <p data-booking-capacity-warning class="hidden text-xs text-rose-600 dark:text-rose-400 font-semibold mt-1.5"></p>
            </div>
        </div>

        <div class="mt-4">
            <label for="<?php echo $bookingFormPrefix; ?>Purpose" class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">Tujuan / Catatan Tambahan</label>
            <textarea name="purpose" id="<?php echo $bookingFormPrefix; ?>Purpose" rows="3" class="w-full p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition resize-y" placeholder="Contoh: memerlukan mikrofon tambahan atau konfigurasi tempat duduk khusus"><?php echo htmlspecialchars($bookingFormValues['purpose']); ?></textarea>
        </div>
    </section>

    <div class="p-3.5 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800/60 text-sky-900 dark:text-sky-300 text-xs flex items-start gap-2.5">
        <i class="fas fa-info-circle text-sky-600 dark:text-sky-400 text-sm mt-0.5 shrink-0"></i>
        <p class="leading-relaxed"><strong>Informasi:</strong> Jika jadwal beririsan dengan agenda lain, booking tetap diterima sebagai <em>Pending</em> untuk dianalisis Administrator menggunakan metode SAW.</p>
    </div>
</div>
