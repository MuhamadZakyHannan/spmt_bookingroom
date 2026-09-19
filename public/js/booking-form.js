(function () {
    'use strict';

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
                timeZone: 'Asia/Jakarta',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            }).formatToParts(new Date());
            const values = {};
            parts.forEach(part => { values[part.type] = part.value; });
            return `${values.year}-${values.month}-${values.day}`;
        } catch (error) {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
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
            capacityWarning: form.querySelector('[data-booking-capacity-warning]')
        };
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

    function initTimePicker(picker, form) {
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
            } else {
                closeTimePicker(picker);
            }
        });

        panel.addEventListener('click', event => event.stopPropagation());

        if (closeButton) {
            closeButton.addEventListener('click', () => {
                closeTimePicker(picker);
                trigger.focus();
            });
        }

        if (applyButton) {
            applyButton.addEventListener('click', () => {
                const selectedTime = `${hourSelect.value}:${minuteSelect.value}`;
                input.value = selectedTime;
                if (valueLabel) valueLabel.textContent = selectedTime;
                input.dispatchEvent(new Event('change', { bubbles: true }));
                closeTimePicker(picker);
                trigger.focus();
            });
        }

        picker.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeTimePicker(picker);
                trigger.focus();
            }
        });

        if (valueLabel) valueLabel.textContent = input.value;
    }

    function validate(form) {
        const elements = getElements(form);
        const today = getTodayInJakarta();

        if (!form.checkValidity()) {
            form.reportValidity();
            return { valid: false, message: 'Mohon lengkapi seluruh kolom wajib.' };
        }

        if (elements.date && elements.date.value < today) {
            elements.date.setAttribute('aria-invalid', 'true');
            return { valid: false, message: 'Tanggal pemesanan tidak boleh di masa lalu berdasarkan waktu WIB.' };
        }
        if (elements.date) elements.date.removeAttribute('aria-invalid');

        if (!updateSchedule(form)) {
            return { valid: false, message: 'Jam selesai harus lebih lambat dari jam mulai.' };
        }

        if (!updateCapacity(form)) {
            return { valid: false, message: elements.capacityWarning.textContent };
        }

        return { valid: true, message: '' };
    }

    function refresh(form) {
        const elements = getElements(form);
        const today = getTodayInJakarta();
        if (elements.date) elements.date.min = today;
        updateRoomInformation(form);
        updateSchedule(form);
    }

    function init(form) {
        if (form.dataset.bookingFormInitialized === '1') return;
        form.dataset.bookingFormInitialized = '1';
        const elements = getElements(form);

        if (elements.room) elements.room.addEventListener('change', () => updateRoomInformation(form));
        if (elements.attendees) elements.attendees.addEventListener('input', () => updateCapacity(form));
        if (elements.startTime) elements.startTime.addEventListener('change', () => updateSchedule(form));
        if (elements.endTime) elements.endTime.addEventListener('change', () => updateSchedule(form));
        form.querySelectorAll('[data-time-picker]').forEach(picker => initTimePicker(picker, form));
        form.addEventListener('submit', event => {
            const result = validate(form);
            if (!result.valid) event.preventDefault();
        });

        refresh(form);
    }

    window.BookingFormUI = { init, refresh, validate };
    document.addEventListener('click', () => closeOtherTimePickers(null));
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-booking-form]').forEach(init);
    });
})();
