<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            <i class="fas fa-calendar-alt text-brand-600 dark:text-brand-400"></i> Kalender Jadwal Ruang Rapat
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Visualisasi seluruh agenda pemesanan ruangan secara interaktif real-time.</p>
    </div>

    <!-- Filter by Room -->
    <div class="w-full md:w-72">
        <form method="GET" action="calendar.php" id="calendarFilterForm">
            <select name="room_id" onchange="document.getElementById('calendarFilterForm').submit()" class="w-full px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-100 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-brand-500 shadow-sm cursor-pointer">
                <option value="0">-- Semua Ruangan Rapat --</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?php echo $r['id']; ?>" <?php echo $room_filter === $r['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm p-4 sm:p-6 mb-8">
    <div id="calendar" class="min-h-[600px]"></div>
</div>

<!-- Event Detail Modal -->
<div id="eventModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 max-w-md w-full overflow-hidden transform transition-all">
        <div class="p-5 bg-brand-600 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-calendar-check text-lg"></i>
                <h3 class="font-bold text-base">Detail Rapat</h3>
            </div>
            <button onclick="closeModal()" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="p-6 space-y-4 text-xs text-slate-700 dark:text-slate-200">
            <div>
                <span class="text-slate-400 dark:text-slate-400 font-semibold uppercase text-[10px] block mb-0.5">Judul Agenda</span>
                <div id="modalTitle" class="text-sm font-bold text-slate-900 dark:text-white"></div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-slate-400 dark:text-slate-400 font-semibold uppercase text-[10px] block mb-0.5">Ruangan</span>
                    <div id="modalRoom" class="font-semibold text-brand-600 dark:text-brand-400"></div>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-400 font-semibold uppercase text-[10px] block mb-0.5">Pemesan</span>
                    <div id="modalUser" class="font-semibold"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-slate-400 dark:text-slate-400 font-semibold uppercase text-[10px] block mb-0.5">Tanggal</span>
                    <div id="modalDate" class="font-semibold"></div>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-400 font-semibold uppercase text-[10px] block mb-0.5">Waktu</span>
                    <div id="modalTime" class="font-semibold font-mono"></div>
                </div>
            </div>

            <div>
                <span class="text-slate-400 dark:text-slate-400 font-semibold uppercase text-[10px] block mb-0.5">Jumlah Peserta</span>
                <div id="modalAttendees" class="font-semibold"></div>
            </div>

            <div>
                <span class="text-slate-400 dark:text-slate-400 font-semibold uppercase text-[10px] block mb-0.5">Tujuan / Catatan</span>
                <div id="modalPurpose" class="p-3 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-700 text-slate-600 dark:text-slate-300"></div>
            </div>
        </div>
        <div class="p-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-700 text-right">
            <button onclick="closeModal()" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 font-bold rounded-xl transition text-xs">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        buttonText: {
            today: 'Hari Ini',
            month: 'Bulan',
            week: 'Minggu',
            list: 'Daftar'
        },
        events: 'api/get_events.php?room_id=<?php echo $room_filter; ?>',
        eventClick: function(info) {
            var props = info.event.extendedProps;
            document.getElementById('modalTitle').textContent = props.title;
            document.getElementById('modalRoom').textContent = props.room;
            document.getElementById('modalUser').textContent = props.user;
            document.getElementById('modalDate').textContent = props.date_formatted;
            document.getElementById('modalTime').textContent = props.time;
            document.getElementById('modalAttendees').textContent = props.attendees + ' Orang';
            document.getElementById('modalPurpose').textContent = props.purpose || 'Tidak ada catatan tambahan.';
            
            var modal = document.getElementById('eventModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    });
    calendar.render();
});

function closeModal() {
    var modal = document.getElementById('eventModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
