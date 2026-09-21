<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<style>
    .calendar-shell .fc {
        --fc-border-color: #e5e7eb;
        --fc-page-bg-color: transparent;
        --fc-neutral-bg-color: #f8fafc;
        --fc-list-event-hover-bg-color: #f8fafc;
        color: #1f2937;
        font-size: 0.8125rem;
    }
    .calendar-shell .fc-theme-standard td,
    .calendar-shell .fc-theme-standard th,
    .calendar-shell .fc-theme-standard .fc-scrollgrid {
        border-color: var(--fc-border-color);
    }
    .calendar-shell .fc-scrollgrid {
        border-radius: 0 0 1rem 1rem;
        overflow: hidden;
    }
    .calendar-shell .fc-col-header-cell {
        background: #f8fafc;
        padding: 0.7rem 0;
    }
    .calendar-shell .fc-col-header-cell-cushion {
        color: #64748b;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }
    .calendar-shell .fc-daygrid-day-frame {
        min-height: 108px;
    }
    .calendar-shell .fc-daygrid-day-number {
        align-items: center;
        color: #475569;
        display: inline-flex;
        font-size: 0.75rem;
        font-weight: 600;
        height: 1.75rem;
        justify-content: center;
        margin: 0.25rem;
        width: 1.75rem;
    }
    .calendar-shell .fc-day-today {
        background: #f8fbff !important;
    }
    .calendar-shell .fc-day-today .fc-daygrid-day-number {
        background: #1a73e8;
        border-radius: 9999px;
        color: #fff;
    }
    .calendar-shell .fc-day-other .fc-daygrid-day-number {
        color: #94a3b8;
    }
    .calendar-shell .fc-daygrid-event {
        border: 0 !important;
        border-radius: 4px;
        cursor: pointer;
        margin: 1px 4px;
        padding: 2px 5px;
    }
    .calendar-shell .fc-event-main {
        min-width: 0;
    }
    .calendar-event-content {
        align-items: center;
        display: flex;
        gap: 0.3rem;
        min-width: 0;
        overflow: hidden;
        width: 100%;
    }
    .calendar-event-time {
        flex: 0 0 auto;
        font-size: 0.625rem;
        font-weight: 800;
    }
    .calendar-event-title {
        font-size: 0.6875rem;
        font-weight: 700;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .calendar-event-details {
        min-width: 0;
        overflow: hidden;
    }
    .calendar-event-room {
        display: none;
    }
    .calendar-shell .fc-daygrid-more-link {
        color: #1a73e8;
        font-size: 0.6875rem;
        font-weight: 700;
        margin-left: 5px;
    }
    .calendar-shell .fc-more-popover {
        max-width: calc(100vw - 2rem);
        width: 420px;
        z-index: 30;
    }
    .calendar-shell .fc-more-popover .fc-popover-header {
        align-items: center;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        font-size: 0.75rem;
        font-weight: 800;
        min-height: 2.75rem;
        padding: 0.625rem 0.75rem;
    }
    .calendar-shell .fc-more-popover .fc-popover-body {
        max-height: min(420px, 60vh);
        overflow-x: hidden;
        overflow-y: auto;
        padding: 0.5rem;
    }
    .calendar-shell .fc-more-popover .fc-daygrid-event-harness {
        margin-bottom: 0.35rem;
        position: relative !important;
    }
    .calendar-shell .fc-more-popover .fc-daygrid-event {
        display: block;
        margin: 0;
        min-width: 0;
        overflow: hidden;
        padding: 0.45rem 0.55rem;
        width: 100%;
    }
    .calendar-shell .fc-more-popover .calendar-event-content {
        align-items: start;
        display: grid;
        gap: 0.5rem;
        grid-template-columns: 3rem minmax(0, 1fr);
        white-space: normal;
    }
    .calendar-shell .fc-more-popover .calendar-event-time {
        display: block;
        font-size: 0.6875rem;
        line-height: 1.15rem;
    }
    .calendar-shell .fc-more-popover .calendar-event-title {
        display: block;
        font-size: 0.75rem;
        line-height: 1.15rem;
        overflow-wrap: anywhere;
        text-overflow: clip;
        white-space: normal;
    }
    .calendar-shell .fc-more-popover .calendar-event-room,
    .calendar-shell .fc-list .calendar-event-room {
        display: block;
        font-size: 0.625rem;
        font-weight: 600;
        line-height: 1rem;
        opacity: 0.82;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .calendar-shell .fc-list {
        border: 0;
        border-radius: 0;
    }
    .calendar-shell .fc-list-day-cushion {
        background: #f8fafc;
        padding: 0.75rem 1rem;
    }
    .calendar-shell .fc-list-day-text,
    .calendar-shell .fc-list-day-side-text {
        color: #334155;
        font-size: 0.75rem;
        font-weight: 800;
    }
    .calendar-shell .fc-list-event-time {
        color: #475569;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .calendar-shell .fc-list-event-dot {
        border-width: 5px;
    }
    .calendar-shell .fc-list-empty {
        background: transparent;
        color: #94a3b8;
    }
    .calendar-event-filtered {
        display: none !important;
    }
    .calendar-view-button.is-active {
        background: #e8f0fe;
        color: #1967d2;
    }
    .dark .calendar-shell .fc {
        --fc-border-color: #334155;
        --fc-neutral-bg-color: #0f172a;
        --fc-list-event-hover-bg-color: #334155;
        color: #e2e8f0;
    }
    .dark .calendar-shell .fc-col-header-cell,
    .dark .calendar-shell .fc-list-day-cushion,
    .dark .calendar-shell .fc-more-popover .fc-popover-header {
        background: #0f172a;
    }
    .dark .calendar-shell .fc-more-popover .fc-popover-header {
        border-bottom-color: #334155;
    }
    .dark .calendar-shell .fc-col-header-cell-cushion,
    .dark .calendar-shell .fc-daygrid-day-number,
    .dark .calendar-shell .fc-list-day-text,
    .dark .calendar-shell .fc-list-day-side-text,
    .dark .calendar-shell .fc-list-event-time {
        color: #cbd5e1;
    }
    .dark .calendar-shell .fc-day-today {
        background: rgba(30, 64, 175, 0.12) !important;
    }
    .dark .calendar-shell .fc-day-today .fc-daygrid-day-number {
        color: #fff;
    }
    .dark .calendar-shell .fc-day-other .fc-daygrid-day-number {
        color: #64748b;
    }
    .dark .calendar-view-button.is-active {
        background: rgba(37, 99, 235, 0.28);
        color: #bfdbfe;
    }
    @media (max-width: 640px) {
        .calendar-shell .fc-daygrid-day-frame {
            min-height: 78px;
        }
        .calendar-shell .fc-daygrid-event {
            margin-left: 2px;
            margin-right: 2px;
            padding-left: 3px;
            padding-right: 3px;
        }
        .calendar-event-time {
            display: none;
        }
        .calendar-shell .fc-more-popover {
            width: calc(100vw - 2rem);
        }
    }
</style>

<div class="calendar-shell overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
    <header class="border-b border-slate-200 dark:border-slate-700 px-4 py-4 sm:px-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex min-w-0 flex-wrap items-center gap-2">
                <a href="booking.php" class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm shadow-brand-500/20 transition hover:bg-brand-700">
                    <i class="fas fa-plus"></i>
                    <span>Buat booking</span>
                </a>
                <button type="button" id="calendarTodayButton" class="rounded-lg border border-slate-300 dark:border-slate-600 px-3.5 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    Hari ini
                </button>
                <div class="flex items-center">
                    <button type="button" id="calendarPrevButton" class="h-9 w-9 rounded-full text-slate-500 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition" aria-label="Bulan sebelumnya" title="Sebelumnya">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </button>
                    <button type="button" id="calendarNextButton" class="h-9 w-9 rounded-full text-slate-500 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition" aria-label="Bulan berikutnya" title="Berikutnya">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </button>
                </div>
                <div class="min-w-0 pl-1">
                    <h1 id="calendarPeriodTitle" class="truncate text-lg font-semibold text-slate-800 dark:text-white sm:text-xl">Kalender</h1>
                    <p class="text-[10px] font-medium text-slate-400"><span id="calendarEventCount">0</span> jadwal ditampilkan</p>
                </div>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative min-w-0 sm:w-64">
                    <i class="fas fa-search absolute left-3 top-2.5 text-xs text-slate-400"></i>
                    <input type="search" id="calendarSearchInput" autocomplete="off" placeholder="Cari agenda atau ruangan" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 py-2 pl-9 pr-8 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <button type="button" id="calendarSearchClear" class="absolute right-2.5 top-2 hidden text-slate-400 hover:text-slate-700 dark:hover:text-white" aria-label="Hapus pencarian">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-600 p-1">
                    <button type="button" data-calendar-view="dayGridMonth" class="calendar-view-button is-active rounded-md px-3 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 transition">Bulan</button>
                    <button type="button" data-calendar-view="listMonth" class="calendar-view-button rounded-md px-3 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 transition">Agenda</button>
                </div>
            </div>
        </div>

        <form method="GET" action="calendar.php" class="mt-3 lg:hidden">
            <label for="calendarRoomMobile" class="sr-only">Filter ruangan</label>
            <select id="calendarRoomMobile" name="room_id" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="0">Semua ruangan</option>
                <?php foreach ($rooms as $room): ?>
                    <option value="<?php echo (int)$room['id']; ?>" <?php echo $room_filter === (int)$room['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($room['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </header>

    <div class="flex min-w-0">
        <aside class="hidden w-64 shrink-0 border-r border-slate-200 dark:border-slate-700 p-5 lg:block">
            <div class="rounded-2xl bg-brand-50 dark:bg-brand-950/30 p-4 text-center">
                <div class="text-[10px] font-bold uppercase tracking-wider text-brand-600 dark:text-brand-300">Hari ini</div>
                <div class="mt-1 text-4xl font-light text-brand-700 dark:text-brand-200"><?php echo date('d'); ?></div>
                <div class="mt-1 text-xs font-semibold text-slate-600 dark:text-slate-300"><?php echo format_date(date('Y-m-d')); ?></div>
            </div>

            <div class="mt-6">
                <h2 class="mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Kalender ruangan</h2>
                <a href="calendar.php" class="flex items-center gap-3 rounded-lg px-2 py-2 text-xs font-semibold transition <?php echo $room_filter === 0 ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/60'; ?>">
                    <span class="h-3 w-3 rounded-sm bg-brand-500"></span>
                    <span class="truncate">Semua ruangan</span>
                </a>
                <div class="mt-1 space-y-0.5">
                    <?php foreach ($rooms as $room): ?>
                        <a href="calendar.php?room_id=<?php echo (int)$room['id']; ?>" class="flex items-center gap-3 rounded-lg px-2 py-2 text-xs font-semibold transition <?php echo $room_filter === (int)$room['id'] ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/60'; ?>">
                            <span class="h-3 w-3 shrink-0 rounded-sm" style="background-color: <?php echo htmlspecialchars($room['calendar_color']); ?>"></span>
                            <span class="truncate"><?php echo htmlspecialchars($room['name']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mt-6 border-t border-slate-100 dark:border-slate-700 pt-4 text-[10px] leading-relaxed text-slate-400">
                Klik tanggal kosong untuk membuat booking pada tanggal tersebut. Klik agenda untuk melihat detail rapat.
            </div>
        </aside>

        <section class="min-w-0 flex-1 p-3 sm:p-5" aria-label="Kalender jadwal rapat">
            <div class="relative min-h-[520px]">
                <div id="calendarLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/90 dark:bg-slate-800/90 text-sm text-slate-400">
                    <i class="fas fa-circle-notch fa-spin mr-2 text-brand-500"></i> Memuat kalender...
                </div>
                <div id="calendar" class="min-h-[520px] opacity-0 transition-opacity"></div>
                <div id="calendarLoadError" class="absolute inset-0 hidden items-center justify-center bg-white dark:bg-slate-800 text-center">
                    <div>
                        <i class="fas fa-calendar-times mb-3 text-4xl text-slate-300"></i>
                        <p class="font-bold text-slate-700 dark:text-slate-200">Kalender gagal dimuat</p>
                        <p class="mt-1 text-xs text-slate-400">Periksa koneksi internet lalu muat ulang halaman.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<div id="eventModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-2xl">
        <div class="flex items-start justify-between px-6 pb-3 pt-5">
            <div class="flex min-w-0 items-start gap-3">
                <span id="modalColor" class="mt-1 h-3 w-3 shrink-0 rounded-sm bg-brand-500"></span>
                <div class="min-w-0">
                    <h3 id="modalTitle" class="text-lg font-semibold text-slate-900 dark:text-white"></h3>
                    <p id="modalDateTime" class="mt-1 text-xs text-slate-500 dark:text-slate-400"></p>
                </div>
            </div>
            <button type="button" onclick="closeCalendarEventModal()" class="ml-3 h-9 w-9 shrink-0 rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-700 dark:hover:text-white transition" aria-label="Tutup detail">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="space-y-4 px-6 pb-6 pt-2 text-sm">
            <div class="flex items-start gap-3">
                <i class="fas fa-door-open mt-0.5 w-4 text-center text-slate-400"></i>
                <div>
                    <div id="modalRoom" class="font-semibold text-slate-800 dark:text-slate-100"></div>
                    <div class="text-xs text-slate-400">Ruang rapat</div>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <i class="fas fa-user mt-0.5 w-4 text-center text-slate-400"></i>
                <div>
                    <div id="modalUser" class="font-semibold text-slate-800 dark:text-slate-100"></div>
                    <div id="modalAttendees" class="text-xs text-slate-400"></div>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <i class="fas fa-align-left mt-0.5 w-4 text-center text-slate-400"></i>
                <div id="modalPurpose" class="whitespace-pre-wrap text-xs leading-relaxed text-slate-600 dark:text-slate-300"></div>
            </div>
        </div>
    </div>
</div>

<script>
    let calendarInstance = null;

    function closeCalendarEventModal() {
        const modal = document.getElementById('eventModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const calendarElement = document.getElementById('calendar');
        const loadingElement = document.getElementById('calendarLoading');
        const errorElement = document.getElementById('calendarLoadError');

        if (typeof FullCalendar === 'undefined') {
            loadingElement.classList.add('hidden');
            errorElement.classList.remove('hidden');
            errorElement.classList.add('flex');
            return;
        }

        const periodTitle = document.getElementById('calendarPeriodTitle');
        const eventCount = document.getElementById('calendarEventCount');
        const searchInput = document.getElementById('calendarSearchInput');
        const searchClear = document.getElementById('calendarSearchClear');
        let searchQuery = '';

        const capitalize = value => value.charAt(0).toUpperCase() + value.slice(1);
        const matchesSearch = event => {
            if (!searchQuery) return true;
            const props = event.extendedProps || {};
            return [event.title, props.title, props.room, props.user, props.purpose]
                .some(value => String(value || '').toLowerCase().includes(searchQuery));
        };

        const updateVisibleEventCount = () => {
            if (!calendarInstance) return;
            const view = calendarInstance.view;
            const total = calendarInstance.getEvents().filter(event => {
                return event.start >= view.activeStart && event.start < view.activeEnd && matchesSearch(event);
            }).length;
            eventCount.textContent = total;
        };

        const updateViewButtons = viewName => {
            document.querySelectorAll('[data-calendar-view]').forEach(button => {
                button.classList.toggle('is-active', button.dataset.calendarView === viewName);
                button.setAttribute('aria-pressed', button.dataset.calendarView === viewName ? 'true' : 'false');
            });
        };

        calendarInstance = new FullCalendar.Calendar(calendarElement, {
            initialView: 'dayGridMonth',
            headerToolbar: false,
            firstDay: 1,
            fixedWeekCount: false,
            height: 'auto',
            dayMaxEvents: 4,
            displayEventEnd: false,
            eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            events: 'api/get_events.php?room_id=<?php echo (int)$room_filter; ?>',
            eventSourceFailure: () => {
                loadingElement.classList.add('hidden');
                calendarElement.classList.add('opacity-0');
                errorElement.classList.remove('hidden');
                errorElement.classList.add('flex');
            },
            noEventsContent: 'Tidak ada jadwal pada periode ini.',
            moreLinkContent: args => `+${args.num} lainnya`,
            dayHeaderContent: args => ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'][args.date.getDay()],
            eventClassNames: args => matchesSearch(args.event) ? [] : ['calendar-event-filtered'],
            eventContent: args => {
                const wrapper = document.createElement('div');
                wrapper.className = 'calendar-event-content';

                const time = document.createElement('span');
                time.className = 'calendar-event-time';
                time.textContent = args.timeText;

                const details = document.createElement('span');
                details.className = 'calendar-event-details';

                const title = document.createElement('span');
                title.className = 'calendar-event-title';
                title.textContent = args.event.extendedProps.title || args.event.title;

                const room = document.createElement('span');
                room.className = 'calendar-event-room';
                room.textContent = args.event.extendedProps.room || '';

                if (args.timeText) wrapper.appendChild(time);
                details.appendChild(title);
                details.appendChild(room);
                wrapper.appendChild(details);
                return { domNodes: [wrapper] };
            },
            eventDidMount: args => {
                const props = args.event.extendedProps;
                args.el.setAttribute('title', `${props.time} · ${props.room} · ${props.title}`);
            },
            dateClick: args => {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const selectedDate = new Date(args.dateStr + 'T00:00:00');
                if (selectedDate >= today) {
                    const roomQuery = <?php echo (int)$room_filter; ?> > 0 ? '&room_id=<?php echo (int)$room_filter; ?>' : '';
                    window.location.href = `booking.php?date=${encodeURIComponent(args.dateStr)}${roomQuery}`;
                }
            },
            eventClick: args => {
                const props = args.event.extendedProps;
                document.getElementById('modalTitle').textContent = props.title;
                document.getElementById('modalDateTime').textContent = `${props.date_formatted} · ${props.time} WIB`;
                document.getElementById('modalRoom').textContent = props.room;
                document.getElementById('modalUser').textContent = props.user;
                document.getElementById('modalAttendees').textContent = `${props.attendees} peserta`;
                document.getElementById('modalPurpose').textContent = props.purpose || 'Tidak ada catatan tambahan.';
                document.getElementById('modalColor').style.backgroundColor = args.event.backgroundColor;

                const modal = document.getElementById('eventModal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            },
            datesSet: info => {
                const title = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(info.view.currentStart);
                periodTitle.textContent = capitalize(title);
                updateViewButtons(info.view.type);
                updateVisibleEventCount();
            },
            eventsSet: updateVisibleEventCount,
            loading: isLoading => {
                if (!isLoading) {
                    loadingElement.classList.add('hidden');
                    calendarElement.classList.remove('opacity-0');
                }
            }
        });

        calendarInstance.render();

        document.getElementById('calendarTodayButton').addEventListener('click', () => calendarInstance.today());
        document.getElementById('calendarPrevButton').addEventListener('click', () => calendarInstance.prev());
        document.getElementById('calendarNextButton').addEventListener('click', () => calendarInstance.next());

        document.querySelectorAll('[data-calendar-view]').forEach(button => {
            button.addEventListener('click', () => calendarInstance.changeView(button.dataset.calendarView));
        });

        searchInput.addEventListener('input', () => {
            searchQuery = searchInput.value.trim().toLowerCase();
            searchClear.classList.toggle('hidden', searchQuery === '');
            calendarInstance.rerenderEvents();
            updateVisibleEventCount();
        });

        searchClear.addEventListener('click', () => {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        });

        document.getElementById('eventModal').addEventListener('click', event => {
            if (event.target.id === 'eventModal') closeCalendarEventModal();
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeCalendarEventModal();
        });
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
