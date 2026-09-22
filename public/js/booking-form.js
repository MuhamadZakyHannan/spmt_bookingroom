(function () {
    'use strict';

    const formStates = new WeakMap();

    function parseTime(value) {
        const parts = String(value || '').split(':');
        if (parts.length !== 2) return null;
        const hours = Number.parseInt(parts[0], 10);
        const minutes = Number.parseInt(parts[1], 10);
        if (!Number.isInteger(hours) || !Number.isInteger(minutes)) return null;
        return (hours * 60) + minutes;
    }

    function getTodayInJakarta() {
        try {
            const parts = new Intl.DateTimeFormat('en-CA', {
                timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit'
            }).formatToParts(new Date());
            const values = {};
            parts.forEach(part => { values[part.type] = part.value; });
            return `${values.year}-${values.month}-${values.day}`;
        } catch (error) {
            const now = new Date();
            return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
        }
    }

    function formatDuration(totalMinutes) {
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        const parts = [];
        if (hours > 0) parts.push(`${hours} jam`);
        if (minutes > 0) parts.push(`${minutes} menit`);
        return parts.join(' ') || '0 menit';
    }

    function getElements(form) {
        return {
            room: form.querySelector('[data-booking-room]'),
            roomInfo: form.querySelector('[data-booking-room-info]'),
            roomCapacity: form.querySelector('[data-room-capacity]'),
            roomLocation: form.querySelector('[data-room-location]'),
            roomFacilities: form.querySelector('[data-room-facilities]'),
            date: form.querySelector('[data-booking-date]'),
            startTime: form.querySelector('[data-booking-start-time]'),
            endTime: form.querySelector('[data-booking-end-time]'),
            duration: form.querySelector('[data-booking-duration] span'),
            scheduleError: form.querySelector('[data-booking-schedule-error]'),
            attendees: form.querySelector('[data-booking-attendees]'),
            capacityWarning: form.querySelector('[data-booking-capacity-warning]'),
            availabilityMessage: form.querySelector('[data-availability-message]'),
            availabilityLoading: form.querySelector('[data-availability-loading]'),
            availabilityList: form.querySelector('[data-availability-list]')
        };
    }

    function getState(form) {
        if (!formStates.has(form)) {
            formStates.set(form, { timer: null, controller: null, requestNumber: 0 });
        }
        return formStates.get(form);
    }

    function updateRoomInformation(form) {
        const elements = getElements(form);
        if (!elements.room) return;
        const option = elements.room.options[elements.room.selectedIndex];
        const hasRoom = option && option.value && option.dataset.capacity;

        if (!hasRoom) {
            if (elements.roomInfo) elements.roomInfo.classList.add('hidden');
            updateCapacity(form);
            return;
        }

        if (elements.roomCapacity) elements.roomCapacity.textContent = `Kapasitas ${option.dataset.capacity} orang`;
        if (elements.roomLocation) elements.roomLocation.textContent = option.dataset.location || '-';
        if (elements.roomFacilities) elements.roomFacilities.textContent = option.dataset.facilities || '-';
        if (elements.roomInfo) elements.roomInfo.classList.remove('hidden');
        updateCapacity(form);
    }

    function updateCapacity(form) {
        const elements = getElements(form);
        if (!elements.capacityWarning || !elements.room || !elements.attendees) return true;
        const option = elements.room.options[elements.room.selectedIndex];
        const capacity = option && option.dataset.capacity ? Number.parseInt(option.dataset.capacity, 10) : 0;
        const attendees = Number.parseInt(elements.attendees.value, 10) || 0;

        if (capacity > 0 && attendees > capacity) {
            elements.capacityWarning.textContent = `Jumlah peserta (${attendees} orang) melebihi kapasitas ${option.dataset.name || 'ruangan'} (${capacity} orang).`;
            elements.capacityWarning.classList.remove('hidden');
            elements.attendees.setAttribute('aria-invalid', 'true');
            return false;
        }

        elements.capacityWarning.textContent = '';
        elements.capacityWarning.classList.add('hidden');
        elements.attendees.removeAttribute('aria-invalid');
        return true;
    }

    function updateSchedule(form) {
        const elements = getElements(form);
        if (!elements.startTime || !elements.endTime) return true;
        const start = parseTime(elements.startTime.value);
        const end = parseTime(elements.endTime.value);
        const valid = start !== null && end !== null && end > start;

        if (valid) {
            if (elements.duration) elements.duration.textContent = `Durasi: ${formatDuration(end - start)}`;
            if (elements.scheduleError) {
                elements.scheduleError.textContent = '';
                elements.scheduleError.classList.add('hidden');
            }
            elements.endTime.removeAttribute('aria-invalid');
            return true;
        }

        if (elements.duration) elements.duration.textContent = 'Durasi belum valid';
        if (elements.scheduleError) {
            elements.scheduleError.textContent = 'Jam selesai harus lebih lambat dari jam mulai.';
            elements.scheduleError.classList.remove('hidden');
        }
        elements.endTime.setAttribute('aria-invalid', 'true');
        return false;
    }

    function resetRoomOptions(roomSelect) {
        if (!roomSelect) return;
        Array.from(roomSelect.options).forEach(option => {
            if (!option.value) return;
            option.textContent = option.dataset.originalLabel || option.textContent;
            option.disabled = option.dataset.roomStatus === 'maintenance';
            delete option.dataset.availabilitySelectable;
        });
    }

    function statusStyle(status) {
        const styles = {
            available: {
                card: 'border-emerald-200 bg-emerald-50/70 text-emerald-950 hover:border-emerald-400 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-100',
                badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/70 dark:text-emerald-200', dot: 'bg-emerald-500'
            },
            pending_conflict: {
                card: 'border-amber-200 bg-amber-50/70 text-amber-950 hover:border-amber-400 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100',
                badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/70 dark:text-amber-200', dot: 'bg-amber-500'
            },
            confirmed_conflict: {
                card: 'border-rose-200 bg-rose-50/70 text-rose-950 opacity-75 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-100',
                badge: 'bg-rose-100 text-rose-700 dark:bg-rose-900/70 dark:text-rose-200', dot: 'bg-rose-500'
            },
            unavailable: {
                card: 'border-slate-200 bg-slate-100/80 text-slate-700 opacity-75 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200',
                badge: 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-200', dot: 'bg-slate-400'
            }
        };
        return styles[status] || styles.unavailable;
    }

    function renderAvailabilityCards(form, rooms) {
        const elements = getElements(form);
        if (!elements.availabilityList) return;
        elements.availabilityList.replaceChildren();

        rooms.forEach(room => {
            const styleKey = ['maintenance', 'insufficient_capacity'].includes(room.availability_status)
                ? 'unavailable' : room.availability_status;
            const style = statusStyle(styleKey);
            const button = document.createElement('button');
            button.type = 'button';
            button.disabled = !room.selectable;
            button.className = `w-full rounded-xl border p-3 text-left transition focus:outline-none focus:ring-2 focus:ring-brand-500 disabled:cursor-not-allowed ${style.card}`;

            const header = document.createElement('div');
            header.className = 'flex items-start justify-between gap-3';
            const identity = document.createElement('div');
            identity.className = 'min-w-0';
            const name = document.createElement('p');
            name.className = 'text-sm font-bold truncate';
            name.textContent = room.name;
            const meta = document.createElement('p');
            meta.className = 'mt-0.5 text-[11px] opacity-75';
            meta.textContent = `${room.capacity} orang · ${room.location}`;
            identity.append(name, meta);

            const badge = document.createElement('span');
            badge.className = `shrink-0 inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-[10px] font-bold ${style.badge}`;
            const dot = document.createElement('span');
            dot.className = `h-1.5 w-1.5 rounded-full ${style.dot}`;
            const badgeText = document.createElement('span');
            badgeText.textContent = room.label;
            badge.append(dot, badgeText);
            header.append(identity, badge);

            const message = document.createElement('p');
            message.className = 'mt-2 text-[11px] leading-relaxed opacity-80';
            message.textContent = room.message;
            button.append(header, message);

            if (room.selectable) {
                button.addEventListener('click', () => {
                    elements.room.value = String(room.id);
                    elements.room.dispatchEvent(new Event('change', { bubbles: true }));
                });
            }
            elements.availabilityList.append(button);
        });
    }

    function applyAvailability(form, payload) {
        const elements = getElements(form);
        const roomsById = new Map(payload.rooms.map(room => [String(room.id), room]));
        Array.from(elements.room.options).forEach(option => {
            if (!option.value) return;
            const room = roomsById.get(option.value);
            if (!room) return;
            const baseLabel = option.dataset.originalLabel || option.textContent;
            option.dataset.originalLabel = baseLabel;
            option.dataset.availabilitySelectable = room.selectable ? '1' : '0';
            option.disabled = !room.selectable;
            option.textContent = `${baseLabel} · ${room.label}`;
        });

        const selectedOption = elements.room.options[elements.room.selectedIndex];
        if (selectedOption && selectedOption.value && selectedOption.disabled) {
            elements.room.value = '';
            elements.room.dispatchEvent(new Event('change', { bubbles: true }));
        }

        renderAvailabilityCards(form, payload.rooms);
        if (elements.availabilityMessage) {
            const availableCount = payload.summary.available;
            const competingCount = payload.summary.pending_conflict;
            elements.availabilityMessage.textContent = `${availableCount} ruangan tersedia${competingCount > 0 ? `, ${competingCount} memiliki pengajuan lain yang masih menunggu persetujuan` : ''}. Klik kartu hijau atau kuning untuk memilih.`;
        }
    }

    function availabilityInputIsValid(elements) {
        const attendees = Number.parseInt(elements.attendees?.value || '', 10);
        const start = parseTime(elements.startTime?.value);
        const end = parseTime(elements.endTime?.value);
        return Boolean(elements.date?.value && elements.date.value >= getTodayInJakarta()
            && start !== null && end !== null && end > start && attendees >= 1 && attendees <= 100);
    }

    async function requestAvailability(form) {
        const elements = getElements(form);
        const state = getState(form);
        const endpoint = form.dataset.availabilityUrl;
        resetRoomOptions(elements.room);

        if (!endpoint || !availabilityInputIsValid(elements)) {
            if (elements.availabilityList) elements.availabilityList.replaceChildren();
            if (elements.availabilityMessage) elements.availabilityMessage.textContent = 'Lengkapi tanggal, waktu, dan jumlah peserta untuk melihat ketersediaan.';
            if (elements.availabilityLoading) elements.availabilityLoading.classList.add('hidden');
            return;
        }

        if (state.controller) state.controller.abort();
        state.controller = new AbortController();
        const requestNumber = ++state.requestNumber;
        if (elements.availabilityLoading) elements.availabilityLoading.classList.remove('hidden');
        if (elements.availabilityMessage) elements.availabilityMessage.textContent = 'Memeriksa jadwal terbaru…';

        const query = new URLSearchParams({
            date: elements.date.value, start_time: elements.startTime.value,
            end_time: elements.endTime.value, attendees_count: elements.attendees.value
        });
        if (form.dataset.excludeBookingId) {
            query.set('exclude_booking_id', form.dataset.excludeBookingId);
        }

        try {
            const response = await fetch(`${endpoint}?${query.toString()}`, {
                method: 'GET', credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: state.controller.signal
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.error || 'Ketersediaan ruangan tidak dapat dimuat.');
            if (requestNumber === state.requestNumber) applyAvailability(form, payload);
        } catch (error) {
            if (error.name === 'AbortError') return;
            if (elements.availabilityList) elements.availabilityList.replaceChildren();
            if (elements.availabilityMessage) elements.availabilityMessage.textContent = `${error.message} Validasi tetap akan dilakukan saat booking dikirim.`;
        } finally {
            if (requestNumber === state.requestNumber && elements.availabilityLoading) elements.availabilityLoading.classList.add('hidden');
        }
    }

    function scheduleAvailability(form, delay = 300) {
        const state = getState(form);
        if (state.controller) {
            state.controller.abort();
            state.controller = null;
        }
        state.requestNumber++;
        window.clearTimeout(state.timer);
        state.timer = window.setTimeout(() => requestAvailability(form), delay);
    }

    function closeTimePicker(picker) {
        if (!picker) return;
        const panel = picker.querySelector('[data-time-picker-panel]');
        const trigger = picker.querySelector('[data-time-picker-trigger]');
        if (panel) panel.classList.add('hidden');
        if (trigger) trigger.setAttribute('aria-expanded', 'false');
    }

    function closeOtherTimePickers(activePicker) {
        document.querySelectorAll('[data-time-picker]').forEach(picker => {
            if (picker !== activePicker) closeTimePicker(picker);
        });
    }

    function initTimePicker(picker) {
        const input = picker.querySelector('input[type="hidden"]');
        const trigger = picker.querySelector('[data-time-picker-trigger]');
        const panel = picker.querySelector('[data-time-picker-panel]');
        const valueLabel = picker.querySelector('[data-time-picker-value]');
        const hourSelect = picker.querySelector('[data-time-hour]');
        const minuteSelect = picker.querySelector('[data-time-minute]');
        const applyButton = picker.querySelector('[data-time-picker-apply]');
        const closeButton = picker.querySelector('[data-time-picker-close]');
        if (!input || !trigger || !panel || !hourSelect || !minuteSelect) return;

        function syncSelectors() {
            const parts = String(input.value || '00:00').split(':');
            hourSelect.value = String(parts[0] || '00').padStart(2, '0');
            minuteSelect.value = String(parts[1] || '00').padStart(2, '0');
        }

        trigger.addEventListener('click', event => {
            event.stopPropagation();
            const willOpen = panel.classList.contains('hidden');
            closeOtherTimePickers(picker);
            if (willOpen) {
                syncSelectors();
                panel.classList.remove('hidden');
                trigger.setAttribute('aria-expanded', 'true');
                window.setTimeout(() => hourSelect.focus(), 0);
            } else closeTimePicker(picker);
        });
        panel.addEventListener('click', event => event.stopPropagation());
        if (closeButton) closeButton.addEventListener('click', () => { closeTimePicker(picker); trigger.focus(); });
        if (applyButton) applyButton.addEventListener('click', () => {
            const selectedTime = `${hourSelect.value}:${minuteSelect.value}`;
            input.value = selectedTime;
            if (valueLabel) valueLabel.textContent = selectedTime;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            closeTimePicker(picker);
            trigger.focus();
        });
        picker.addEventListener('keydown', event => {
            if (event.key === 'Escape') { closeTimePicker(picker); trigger.focus(); }
        });
        if (valueLabel) valueLabel.textContent = input.value;
    }

    function validateDocumentInput(documentInput) {
        if (!documentInput) return { valid: true, message: '' };
        if (documentInput) documentInput.setCustomValidity('');
        if (!documentInput.files?.length) return { valid: true, message: '' };

        const file = documentInput.files[0];
        const extension = String(file.name || '').split('.').pop().toLowerCase();
        const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        const allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
        if (file.size > 5 * 1024 * 1024) {
            documentInput.setCustomValidity('Ukuran dokumen maksimal 5 MB.');
            documentInput.reportValidity();
            return { valid: false, message: 'Ukuran dokumen maksimal 5 MB.' };
        }
        if (!allowedExtensions.includes(extension) || (file.type && !allowedMimes.includes(file.type))) {
            documentInput.setCustomValidity('Format dokumen harus PDF, JPG, atau PNG.');
            documentInput.reportValidity();
            return { valid: false, message: 'Format dokumen harus PDF, JPG, atau PNG.' };
        }
        return { valid: true, message: '' };
    }

    function validate(form) {
        const elements = getElements(form);
        const today = getTodayInJakarta();
        const documentInput = form.querySelector('input[name="supporting_document"]');
        if (documentInput) documentInput.setCustomValidity('');
        if (!form.checkValidity()) {
            form.reportValidity();
            return { valid: false, message: 'Mohon lengkapi seluruh kolom wajib.' };
        }
        if (elements.date && elements.date.value < today) {
            elements.date.setAttribute('aria-invalid', 'true');
            return { valid: false, message: 'Tanggal pemesanan tidak boleh di masa lalu berdasarkan waktu WIB.' };
        }
        if (elements.date) elements.date.removeAttribute('aria-invalid');
        if (!updateSchedule(form)) return { valid: false, message: 'Jam selesai harus lebih lambat dari jam mulai.' };
        if (!updateCapacity(form)) return { valid: false, message: elements.capacityWarning.textContent };

        const selectedOption = elements.room?.options[elements.room.selectedIndex];
        if (selectedOption?.dataset.availabilitySelectable === '0' || selectedOption?.disabled) {
            return { valid: false, message: 'Ruangan tidak tersedia untuk jadwal atau jumlah peserta yang dipilih.' };
        }
        return validateDocumentInput(documentInput);
    }

    function refresh(form) {
        const elements = getElements(form);
        if (elements.date) elements.date.min = getTodayInJakarta();
        updateRoomInformation(form);
        updateSchedule(form);
        scheduleAvailability(form, 0);
    }

    function init(form) {
        if (form.dataset.bookingFormInitialized === '1') return;
        form.dataset.bookingFormInitialized = '1';
        const elements = getElements(form);

        if (elements.room) {
            Array.from(elements.room.options).forEach(option => {
                if (option.value && !option.dataset.originalLabel) option.dataset.originalLabel = option.textContent.trim();
            });
            elements.room.addEventListener('change', () => updateRoomInformation(form));
        }
        if (elements.attendees) elements.attendees.addEventListener('input', () => { updateCapacity(form); scheduleAvailability(form); });
        if (elements.date) elements.date.addEventListener('change', () => scheduleAvailability(form));
        if (elements.startTime) elements.startTime.addEventListener('change', () => { updateSchedule(form); scheduleAvailability(form); });
        if (elements.endTime) elements.endTime.addEventListener('change', () => { updateSchedule(form); scheduleAvailability(form); });
        const documentInput = form.querySelector('input[name="supporting_document"]');
        if (documentInput) documentInput.addEventListener('change', () => documentInput.setCustomValidity(''));
        form.querySelectorAll('[data-time-picker]').forEach(picker => initTimePicker(picker));
        form.addEventListener('submit', event => {
            const result = validate(form);
            if (!result.valid) event.preventDefault();
        });
        refresh(form);
    }

    window.BookingFormUI = { init, refresh, validate, requestAvailability };
    document.addEventListener('click', () => closeOtherTimePickers(null));
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-booking-form]').forEach(init);
        document.querySelectorAll('[data-supporting-document-form]').forEach(form => {
            const documentInput = form.querySelector('input[name="supporting_document"]');
            if (documentInput) documentInput.addEventListener('change', () => documentInput.setCustomValidity(''));
            form.addEventListener('submit', event => {
                if (!validateDocumentInput(documentInput).valid) event.preventDefault();
            });
        });
    });
})();
