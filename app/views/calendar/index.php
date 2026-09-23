<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/locales-all.global.min.js"></script>

<div class="calendar-shell rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-sm">
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
                    <i class="fas fa-search absolute left-3 top-2.5 text-xs text-slate-400 pointer-events-none"></i>
                    <input 
                        type="text" 
                        id="calendarSearchInput" 
                        autocomplete="off" 
                        placeholder="Cari agenda atau ruangan..." 
                        class="w-full rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 py-2 pl-9 pr-8 text-xs text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500"
                    >
                    <button 
                        type="button" 
                        id="calendarSearchClear" 
                        class="absolute right-3 top-2.5 hidden text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs transition" 
                        aria-label="Hapus pencarian" 
                        title="Hapus pencarian"
                    >
                        <i class="fas fa-times"></i>
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
                            <span class="data-color-swatch h-3 w-3 shrink-0 rounded-sm" style="--swatch-color: <?php echo htmlspecialchars($room['calendar_color']); ?>"></span>
                            <span class="truncate"><?php echo htmlspecialchars($room['name']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mt-6 border-t border-slate-100 dark:border-slate-700 pt-4 text-[10px] leading-relaxed text-slate-400">
                Klik tanggal kosong untuk membuat booking pada tanggal tersebut. Klik agenda untuk melihat detail rapat.
            </div>
        </aside>

        <section class="min-w-0 flex-1 p-2 sm:p-5" aria-label="Kalender jadwal rapat">
            <div class="relative min-h-[460px] sm:min-h-[520px]">
                <div id="calendarLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/90 dark:bg-slate-800/90 text-sm text-slate-400">
                    <i class="fas fa-circle-notch fa-spin mr-2 text-brand-500"></i> Memuat kalender...
                </div>
                <div id="calendar" class="min-h-[460px] opacity-0 transition-opacity sm:min-h-[520px]"></div>
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

<div id="eventModal" class="responsive-modal fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/55 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="responsive-modal-panel relative w-full max-w-xl overflow-hidden rounded-3xl border border-slate-200/80 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-2xl shadow-slate-950/20">
        <div id="modalAccent" class="absolute inset-x-0 top-0 z-10 h-1 bg-brand-600"></div>
        <div class="calendar-detail-header flex items-start justify-between border-b border-slate-100 dark:border-slate-700 bg-gradient-to-r from-brand-50 via-white to-sky-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 px-5 sm:px-7">
            <div class="flex min-w-0 items-start gap-3.5">
                <span id="modalColor" class="calendar-detail-icon flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-sm ring-4 ring-white/80 dark:ring-slate-800/80">
                    <i class="fas fa-calendar-check"></i>
                </span>
                <div class="min-w-0 pt-0.5">
                    <div class="mb-1.5 flex flex-wrap items-center gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-brand-600 dark:text-brand-300">Detail rapat</span>
                        <span id="modalStatus" data-status="scheduled" class="calendar-detail-status rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide">Terjadwal</span>
                    </div>
                    <h3 id="modalTitle" class="break-words text-lg font-bold leading-snug text-slate-900 dark:text-white sm:text-xl"></h3>
                </div>
            </div>
            <button id="modalCloseButton" type="button" onclick="closeCalendarEventModal()" class="ml-3 mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-transparent text-slate-400 hover:border-slate-200 hover:bg-white hover:text-slate-700 dark:hover:border-slate-600 dark:hover:bg-slate-700 dark:hover:text-white transition" aria-label="Tutup detail">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="space-y-4 p-5 sm:p-6">
            <div class="flex items-center gap-3 rounded-2xl border border-brand-100 dark:border-brand-900/60 bg-brand-50/60 dark:bg-brand-950/20 p-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white dark:bg-slate-800 text-brand-600 dark:text-brand-300 shadow-sm">
                    <i class="far fa-clock"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tanggal dan waktu</div>
                    <div id="modalDateTime" class="mt-0.5 font-bold text-slate-800 dark:text-slate-100"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="flex items-start gap-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/50 p-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-950/70 text-violet-600 dark:text-violet-300">
                        <i class="fas fa-door-open"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ruang rapat</div>
                        <div id="modalRoom" class="mt-0.5 break-words text-sm font-bold text-slate-800 dark:text-slate-100"></div>
                    </div>
                </div>
                <div class="flex items-start gap-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/50 p-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-300">
                        <i class="fas fa-user"></i>
                    </span>
                    <div class="min-w-0">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pemesan</div>
                        <div id="modalUser" class="mt-0.5 break-words text-sm font-bold text-slate-800 dark:text-slate-100"></div>
                        <div id="modalAttendees" class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400"></div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/50 p-4">
                <div class="mb-2 flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    <i class="fas fa-align-left"></i>
                    Tujuan / catatan
                </div>
                <div id="modalPurpose" class="max-h-36 overflow-y-auto whitespace-pre-wrap text-sm leading-relaxed text-slate-700 dark:text-slate-300"></div>
            </div>
        </div>

        <div class="flex justify-end border-t border-slate-100 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/40 px-5 py-4 sm:px-6">
            <button type="button" onclick="closeCalendarEventModal()" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-brand-500/20 transition hover:bg-brand-700">
                <i class="fas fa-check"></i> Selesai
            </button>
        </div>
    </div>
</div>

<script>
    let calendarInstance = null;

    /** Menampilkan atau menutup calendar event modal. */
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
        let cachedCalendarEvents = null;
        let searchDebounceTimer = null;

        /** Mengubah huruf pertama teks menjadi kapital. */
        const capitalize = value => value.charAt(0).toUpperCase() + value.slice(1);
        /** Memeriksa kecocokan agenda terhadap kata pencarian aktif. */
        const matchesSearch = event => {
            if (!searchQuery) return true;
            const props = event.extendedProps || event;
            const title = event.title || props.title || '';
            const room = props.room || '';
            const user = props.user || '';
            const purpose = props.purpose || '';
            return [title, room, user, purpose]
                .some(value => String(value || '').toLowerCase().includes(searchQuery));
        };

        /** Memperbarui jumlah agenda yang terlihat pada periode kalender aktif. */
        const updateVisibleEventCount = () => {
            if (!calendarInstance) return;
            const view = calendarInstance.view;
            const total = calendarInstance.getEvents().filter(event => {
                return event.start >= view.activeStart && event.start < view.activeEnd && matchesSearch(event);
            }).length;
            eventCount.textContent = total;
        };

        /** Menyelaraskan status tombol dengan mode kalender aktif. */
        const updateViewButtons = viewName => {
            document.querySelectorAll('[data-calendar-view]').forEach(button => {
                button.classList.toggle('is-active', button.dataset.calendarView === viewName);
                button.setAttribute('aria-pressed', button.dataset.calendarView === viewName ? 'true' : 'false');
            });
        };

        calendarInstance = new FullCalendar.Calendar(calendarElement, {
            initialView: window.matchMedia('(max-width: 639px)').matches ? 'listMonth' : 'dayGridMonth',
            locale: 'id',
            headerToolbar: false,
            firstDay: 1,
            fixedWeekCount: false,
            height: 'auto',
            dayMaxEvents: window.matchMedia('(max-width: 639px)').matches ? 2 : 4,
            displayEventEnd: false,
            eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            listDayFormat: { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' },
            listDaySideFormat: false,
            events: function(info, successCallback, failureCallback) {
                if (cachedCalendarEvents !== null) {
                    const filtered = searchQuery 
                        ? cachedCalendarEvents.filter(matchesSearch) 
                        : cachedCalendarEvents;
                    successCallback(filtered);
                    return;
                }
                fetch('api/get_events.php?room_id=<?php echo (int)$room_filter; ?>')
                    .then(response => {
                        if (!response.ok) throw new Error('Gagal memuat agenda');
                        return response.json();
                    })
                    .then(data => {
                        cachedCalendarEvents = Array.isArray(data) ? data : [];
                        const filtered = searchQuery 
                            ? cachedCalendarEvents.filter(matchesSearch) 
                            : cachedCalendarEvents;
                        successCallback(filtered);
                    })
                    .catch(err => {
                        failureCallback(err);
                    });
            },
            eventSourceFailure: () => {
                loadingElement.classList.add('hidden');
                calendarElement.classList.add('opacity-0');
                errorElement.classList.remove('hidden');
                errorElement.classList.add('flex');
            },
            noEventsContent: 'Tidak ada jadwal pada periode ini.',
            moreLinkContent: args => `+${args.num} lainnya`,
            dayHeaderContent: args => {
                if (args.view.type.startsWith('list')) {
                    return capitalize(new Intl.DateTimeFormat('id-ID', {
                        weekday: 'long',
                        day: '2-digit',
                        month: 'long',
                        year: 'numeric'
                    }).format(args.date));
                }
                return ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'][args.date.getDay()];
            },
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
                let statusKey = 'scheduled';
                let statusLabel = 'Terjadwal';
                const now = new Date();

                if (args.event.start <= now && args.event.end > now && props.status === 'confirmed') {
                    statusKey = 'ongoing';
                    statusLabel = 'Berlangsung';
                } else if (props.status === 'completed' || args.event.end <= now) {
                    statusKey = 'completed';
                    statusLabel = 'Selesai';
                }

                document.getElementById('modalTitle').textContent = props.title;
                document.getElementById('modalDateTime').textContent = `${props.date_formatted} · ${props.time} WIB`;
                document.getElementById('modalRoom').textContent = props.room;
                document.getElementById('modalUser').textContent = props.user;
                document.getElementById('modalAttendees').textContent = `${props.attendees} peserta`;
                document.getElementById('modalPurpose').textContent = props.purpose || 'Tidak ada catatan tambahan.';
                document.getElementById('modalColor').style.backgroundColor = args.event.backgroundColor;
                document.getElementById('modalAccent').style.backgroundColor = args.event.backgroundColor;
                const modalStatus = document.getElementById('modalStatus');
                modalStatus.dataset.status = statusKey;
                modalStatus.textContent = statusLabel;

                const modal = document.getElementById('eventModal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.getElementById('modalCloseButton').focus();
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

        /** Menyesuaikan posisi dan scroll popover kalender agar tidak terpotong tepi layar. */
        const adjustPopoverPosition = popover => {
            if (!popover) return;
            requestAnimationFrame(() => {
                const rect = popover.getBoundingClientRect();
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
                const margin = 12;

                if (rect.bottom > viewportHeight - margin) {
                    const overflowY = rect.bottom - (viewportHeight - margin);
                    const currentTop = parseFloat(popover.style.top) || 0;
                    popover.style.top = Math.max(margin, currentTop - overflowY) + 'px';
                }

                if (rect.right > viewportWidth - margin) {
                    const overflowX = rect.right - (viewportWidth - margin);
                    const currentLeft = parseFloat(popover.style.left) || 0;
                    popover.style.left = Math.max(margin, currentLeft - overflowX) + 'px';
                }

                const body = popover.querySelector('.fc-popover-body');
                if (body) {
                    body.style.overflowY = 'auto';
                }
            });
        };

        const popoverObserver = new MutationObserver(mutations => {
            for (const mutation of mutations) {
                for (const node of mutation.addedNodes) {
                    if (node.nodeType === 1) {
                        if (node.classList?.contains('fc-popover')) {
                            adjustPopoverPosition(node);
                        } else {
                            const foundPopover = node.querySelector?.('.fc-popover');
                            if (foundPopover) adjustPopoverPosition(foundPopover);
                        }
                    }
                }
            }
        });
        popoverObserver.observe(calendarElement, { childList: true, subtree: true });

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
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                if (calendarInstance) {
                    calendarInstance.refetchEvents();
                    updateVisibleEventCount();
                }
            }, 120);
        });

        searchClear.addEventListener('click', () => {
            searchInput.value = '';
            searchQuery = '';
            searchClear.classList.add('hidden');
            if (calendarInstance) {
                calendarInstance.refetchEvents();
                updateVisibleEventCount();
            }
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
