(function initializeSiteShell() {
    const config = document.getElementById('meetspaceRuntimeConfig')?.dataset ?? {};

    function elements() {
        return {
            drawer: document.getElementById('sidebarDrawer'),
            backdrop: document.getElementById('sidebarBackdrop'),
        };
    }

    window.openSidebar = function openSidebar() {
        const { drawer, backdrop } = elements();
        if (!drawer || !backdrop) return;
        backdrop.classList.remove('hidden');
        window.setTimeout(() => backdrop.classList.remove('opacity-0'), 10);
        drawer.classList.remove('-translate-x-full');
    };

    window.closeSidebar = function closeSidebar() {
        const { drawer, backdrop } = elements();
        if (!drawer || !backdrop) return;
        drawer.classList.add('-translate-x-full');
        backdrop.classList.add('opacity-0');
        window.setTimeout(() => backdrop.classList.add('hidden'), 300);
    };

    window.toggleSidebar = function toggleSidebar() {
        const { drawer } = elements();
        if (!drawer) return;
        drawer.classList.contains('-translate-x-full') ? window.openSidebar() : window.closeSidebar();
    };

    window.updateToggleUI = function updateToggleUI() {
        const dark = document.documentElement.classList.contains('dark');
        document.querySelectorAll('.theme-toggle-knob').forEach(knob => {
            knob.classList.toggle('translate-x-5', dark);
            knob.classList.toggle('translate-x-0', !dark);
        });
        document.querySelectorAll('.theme-toggle-switch').forEach(control => {
            control.classList.toggle('bg-brand-600', dark);
            control.classList.toggle('bg-slate-300', !dark);
            control.setAttribute('aria-checked', String(dark));
        });
        document.querySelectorAll('.theme-toggle-icon').forEach(icon => {
            icon.className = dark
                ? 'theme-toggle-icon fas fa-moon text-amber-400'
                : 'theme-toggle-icon fas fa-sun text-amber-300';
        });
        document.querySelectorAll('.theme-toggle-text').forEach(text => {
            text.textContent = dark ? 'Mode Terang' : 'Mode Gelap';
        });
    };

    window.toggleTheme = function toggleTheme() {
        const dark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', dark ? 'dark' : 'light');
        window.updateToggleUI();
    };

    if (config.adminUserId && config.adminUserId !== '0') initializeAdminNotifications(config);
    document.addEventListener('DOMContentLoaded', window.updateToggleUI);

    function initializeAdminNotifications(runtime) {
        const storageKey = `adminNotificationUnreadCount_${runtime.adminUserId}`;
        let audioContext = null;

        function prepareAudio() {
            if (audioContext) return;
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (AudioContextClass) audioContext = new AudioContextClass();
        }

        function playSound() {
            try {
                prepareAudio();
                if (!audioContext) return;
                if (audioContext.state === 'suspended') audioContext.resume();
                const now = audioContext.currentTime;
                const oscillator = audioContext.createOscillator();
                const gain = audioContext.createGain();
                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(880, now);
                oscillator.frequency.setValueAtTime(1174.66, now + 0.12);
                gain.gain.setValueAtTime(0.0001, now);
                gain.gain.exponentialRampToValueAtTime(0.18, now + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.32);
                oscillator.connect(gain);
                gain.connect(audioContext.destination);
                oscillator.start(now);
                oscillator.stop(now + 0.34);
            } catch (_) {
                // Browser dapat menolak audio sebelum interaksi pengguna.
            }
        }

        function renderCount(count, shouldPlay = true) {
            const badge = document.getElementById('adminNotificationBadge');
            const bell = document.getElementById('adminNotificationBellIcon');
            if (!badge || !bell) return;
            const normalized = Math.max(0, Number.parseInt(count, 10) || 0);
            const previousValue = sessionStorage.getItem(storageKey);
            const previous = previousValue === null ? null : Math.max(0, Number.parseInt(previousValue, 10) || 0);
            badge.textContent = normalized > 99 ? '99+' : String(normalized);
            badge.classList.toggle('hidden', normalized === 0);
            if (shouldPlay && previous !== null && normalized > previous) {
                playSound();
                bell.classList.add('animate-bounce');
                window.setTimeout(() => bell.classList.remove('animate-bounce'), 1200);
            }
            sessionStorage.setItem(storageKey, String(normalized));
        }

        async function poll() {
            try {
                const response = await fetch(`api/admin_notifications.php?t=${Date.now()}`, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                if (!response.ok) return;
                const data = await response.json();
                if (data.success) renderCount(data.unread_count);
            } catch (_) {
                // Gangguan polling tidak boleh mengganggu halaman utama.
            }
        }

        window.openAdminNotifications = async function openAdminNotifications() {
            prepareAudio();
            try {
                const form = new FormData();
                form.append('action', 'mark_all_read');
                form.append('csrf_token', runtime.csrfToken || '');
                const response = await fetch('api/admin_notifications.php', {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: form,
                });
                if (response.ok && (await response.json()).success) renderCount(0, false);
            } catch (_) {
                // Pengguna tetap diarahkan ke daftar booking.
            }
            window.location.href = 'admin_bookings.php';
        };

        document.addEventListener('pointerdown', prepareAudio, { once: true });
        document.addEventListener('DOMContentLoaded', () => {
            poll();
            window.setInterval(poll, 20000);
        });
    }
})();
