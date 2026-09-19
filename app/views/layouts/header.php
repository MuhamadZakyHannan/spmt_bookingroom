<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MeetSpace - Sistem Pemesanan Ruang Rapat Perusahaan (MVC)</title>
    <!-- Tailwind CSS (Local Compiled Standalone) -->
    <link rel="stylesheet" href="public/css/tailwind.min.css">
    <!-- Dark Mode Immediate Detector Script -->
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- FullCalendar 6 -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <style>
        .fc-event {
            cursor: pointer;
            border: none !important;
            border-radius: 6px;
            padding: 3px 6px;
        }
        .dark .fc {
            --fc-page-bg-color: #1e293b;
            --fc-neutral-bg-color: #334155;
            --fc-list-event-hover-bg-color: #334155;
            --fc-theme-standard-border-color: #334155;
            --fc-border-color: #334155;
            color: #f1f5f9;
        }
        .dark .fc-theme-standard td, .dark .fc-theme-standard th {
            border-color: #334155 !important;
        }
        .dark .fc-col-header-cell, .dark .fc-daygrid-day-number {
            color: #cbd5e1;
        }
        .dark .fc-button-primary {
            background-color: #2563eb !important;
            border-color: #1d4ed8 !important;
        }
        .dark .fc-toolbar-title {
            color: #f8fafc !important;
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 font-sans min-h-screen flex flex-col antialiased transition-colors duration-200">

    <!-- Header Navbar -->
    <nav class="bg-gradient-to-r from-white via-sky-50 to-blue-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 text-slate-800 dark:text-white shadow-sm sticky top-0 z-40 border-b border-sky-200/80 dark:border-slate-700/80 transition-colors backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand & Sidebar Toggle (Navigasi terpusat di Sidebar) -->
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="p-2 rounded-xl bg-sky-100/80 hover:bg-sky-200/80 text-sky-900 dark:bg-white/10 dark:hover:bg-white/20 dark:text-white focus:outline-none transition flex items-center gap-2 text-xs font-semibold border border-sky-200/60 dark:border-transparent" title="Buka Sidebar">
                        <i class="fas fa-bars text-sm text-sky-700 dark:text-sky-300"></i>
                        <span class="hidden sm:inline">Menu</span>
                    </button>

                    <a href="dashboard.php" class="flex items-center gap-2 text-xl font-bold tracking-tight hover:opacity-90 transition">
                        <img src="public/logo.png" onerror="this.src='public/logo.svg'" alt="Logo" class="h-8 w-auto object-contain" />
                    </a>
                </div>

                <!-- Right Actions -->
                <div class="flex items-center gap-3">
                    <button onclick="toggleTheme()" class="p-2 rounded-xl bg-sky-100/80 hover:bg-sky-200/80 text-slate-700 dark:bg-white/10 dark:hover:bg-white/20 dark:text-white transition flex items-center gap-2 text-xs font-semibold focus:outline-none border border-sky-200/60 dark:border-transparent" title="Beralih Mode Terang / Gelap">
                        <i class="theme-toggle-icon fas fa-moon text-sky-700 dark:text-amber-300"></i>
                        <span class="hidden lg:inline theme-toggle-text">Mode Gelap</span>
                    </button>

                    <?php if (is_logged_in()): ?>
                    <div class="flex items-center gap-3">
                        <?php if (is_admin()): ?>
                        <button
                            type="button"
                            id="adminNotificationBell"
                            onclick="openAdminNotifications()"
                            class="relative w-10 h-10 rounded-xl bg-sky-100/80 hover:bg-sky-200/80 text-sky-700 dark:bg-white/10 dark:hover:bg-white/20 dark:text-amber-300 transition flex items-center justify-center focus:outline-none border border-sky-200/60 dark:border-transparent"
                            title="Notifikasi booking baru"
                            aria-label="Buka notifikasi booking admin"
                        >
                            <i id="adminNotificationBellIcon" class="fas fa-bell text-base"></i>
                            <span
                                id="adminNotificationBadge"
                                class="hidden absolute -top-1.5 -right-1.5 min-w-5 h-5 px-1 rounded-full bg-rose-600 text-white text-[10px] leading-5 font-bold text-center shadow ring-2 ring-white dark:ring-slate-900"
                                aria-live="polite"
                            >0</span>
                        </button>
                        <?php endif; ?>
                        <div class="hidden sm:block text-right">
                            <div class="text-sm font-semibold text-slate-800 dark:text-white leading-tight"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Pengguna'); ?></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center justify-end gap-1 mt-0.5">
                                <?php if (is_admin()): ?>
                                    <span class="bg-amber-400 text-amber-950 font-bold text-[10px] px-1.5 py-0.5 rounded">ADMIN</span>
                                <?php else: ?>
                                    <span class="bg-sky-600 text-white font-bold text-[10px] px-1.5 py-0.5 rounded">USER</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="relative group">
                            <button class="flex items-center focus:outline-none ring-2 ring-sky-300 dark:ring-white/30 rounded-full">
                                <img src="<?php echo htmlspecialchars($_SESSION['user_avatar'] ?? $_SESSION['avatar'] ?? 'https://via.placeholder.com/40'); ?>" alt="Avatar" class="w-9 h-9 rounded-full object-cover">
                            </button>
                            <div class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 hidden group-hover:block z-50 text-slate-700 dark:text-slate-200">
                                <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-700">
                                    <p class="text-xs text-slate-400 dark:text-slate-400 font-medium">Logged in as</p>
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                                </div>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 font-medium transition">
                                    <i class="fas fa-sign-out-alt w-5"></i> Keluar (Logout)
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="flex gap-2">
                        <a href="login.php" class="px-4 py-2 rounded-lg text-sm font-medium bg-sky-100/80 hover:bg-sky-200 text-sky-900 dark:bg-white/10 dark:text-white transition">Masuk</a>
                        <a href="register.php" class="px-4 py-2 rounded-lg text-sm font-bold bg-sky-600 hover:bg-sky-700 text-white shadow transition">Daftar</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Slide-over Sidebar Drawer -->
    <div id="sidebarBackdrop" onclick="closeSidebar()" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300"></div>
    
    <aside id="sidebarDrawer" class="fixed top-0 left-0 bottom-0 w-80 bg-white dark:bg-slate-800 shadow-2xl z-50 transform -translate-x-full transition-transform duration-300 ease-in-out flex flex-col border-r border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100">
        <div class="p-5 border-b border-sky-100 dark:border-slate-700 flex items-center justify-between bg-gradient-to-r from-white via-sky-50 to-blue-100 dark:from-slate-900 dark:to-slate-800 text-slate-800 dark:text-white">
            <a href="dashboard.php" class="flex items-center gap-2">
                <img src="public/logo.png" onerror="this.src='public/logo.svg'" alt="Logo" class="h-8 w-auto object-contain" />
            </a>
            <button onclick="closeSidebar()" class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-sky-100 dark:text-white/80 dark:hover:text-white dark:hover:bg-white/10 transition focus:outline-none">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-5 space-y-6">
            <div class="p-4 bg-slate-50 dark:bg-slate-900/60 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <i class="theme-toggle-icon fas fa-sun text-base"></i>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-800 dark:text-slate-100">Mode Gelap</div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400">Aktifkan tema gelap</div>
                    </div>
                </div>

                <button id="sidebarThemeSwitch" onclick="toggleTheme()" type="button" class="theme-toggle-switch relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-slate-300 dark:bg-brand-600 transition-colors duration-200 ease-in-out focus:outline-none" role="switch" aria-checked="false">
                    <span class="sr-only">Toggle dark mode</span>
                    <span class="theme-toggle-knob pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out translate-x-0 dark:translate-x-5"></span>
                </button>
            </div>

            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3 px-2">Menu Navigasi</div>
                <nav class="space-y-1">
                    <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?php echo $current_page == 'dashboard.php' ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50'; ?>">
                        <i class="fas fa-th-large w-5 text-center text-brand-600 dark:text-brand-400"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="booking.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?php echo $current_page == 'booking.php' ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50'; ?>">
                        <i class="fas fa-plus-circle w-5 text-center text-emerald-600 dark:text-emerald-400"></i>
                        <span>Pesan Ruangan</span>
                    </a>
                    <a href="calendar.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?php echo $current_page == 'calendar.php' ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50'; ?>">
                        <i class="fas fa-calendar-alt w-5 text-center text-indigo-600 dark:text-indigo-400"></i>
                        <span>Kalender Jadwal</span>
                    </a>
                    <a href="my_bookings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?php echo $current_page == 'my_bookings.php' ? 'bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50'; ?>">
                        <i class="fas fa-bookmark w-5 text-center text-amber-600 dark:text-amber-400"></i>
                        <span>Booking Saya</span>
                    </a>
                </nav>
            </div>

            <!-- Monitor Display Kiosk Section (Admin Only) -->
            <?php if (is_logged_in() && is_admin()): ?>
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3 px-2">Layar Monitor (Kiosk)</div>
                <nav class="space-y-1">
                    <a href="display_lobby.php" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50">
                        <i class="fas fa-desktop w-5 text-center text-blue-600 dark:text-blue-400"></i>
                        <span>Monitor Lobby Utama</span>
                        <span class="ml-auto text-[10px] font-bold px-1.5 py-0.5 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded">LOBBY</span>
                    </a>
                    <a href="display.php" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50">
                        <i class="fas fa-tv w-5 text-center text-amber-600 dark:text-amber-400"></i>
                        <span>Monitor Pintu Ruangan</span>
                        <span class="ml-auto text-[10px] font-bold px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded">PINTU</span>
                    </a>
                </nav>
            </div>
            <?php endif; ?>

            <?php if (is_logged_in() && is_admin()): ?>
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3 px-2">Administrator</div>
                <nav class="space-y-1">
                    <a href="admin_rooms.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?php echo $current_page == 'admin_rooms.php' ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50'; ?>">
                        <i class="fas fa-door-open w-5 text-center text-amber-600 dark:text-amber-400"></i>
                        <span>Kelola Ruangan</span>
                    </a>
                    <a href="admin_displays.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?php echo $current_page == 'admin_displays.php' ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50'; ?>">
                        <i class="fas fa-tv w-5 text-center text-amber-600 dark:text-amber-400"></i>
                        <span>Kelola Monitor Display</span>
                    </a>
                    <a href="admin_bookings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?php echo $current_page == 'admin_bookings.php' ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50'; ?>">
                        <i class="fas fa-tasks w-5 text-center text-amber-600 dark:text-amber-400"></i>
                        <span>Kelola Semua Booking</span>
                    </a>
                    <a href="admin_history.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?php echo $current_page == 'admin_history.php' ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50'; ?>">
                        <i class="fas fa-history w-5 text-center text-amber-600 dark:text-amber-400"></i>
                        <span>Riwayat & Laporan</span>
                    </a>
                    <a href="admin_statistics.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?php echo $current_page == 'admin_statistics.php' ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50'; ?>">
                        <i class="fas fa-chart-pie w-5 text-center text-amber-600 dark:text-amber-400"></i>
                        <span>Statistik & Analisis</span>
                    </a>
                    <a href="admin_users.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition <?php echo $current_page == 'admin_users.php' ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50'; ?>">
                        <i class="fas fa-users w-5 text-center text-amber-600 dark:text-amber-400"></i>
                        <span>Kelola Pengguna</span>
                    </a>
                </nav>
            </div>
            <?php endif; ?>
        </div>

        <?php if (is_logged_in()): ?>
        <div class="p-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
            <div class="flex items-center gap-3 mb-3">
                <img src="<?php echo htmlspecialchars($_SESSION['user_avatar'] ?? $_SESSION['avatar'] ?? 'https://via.placeholder.com/40'); ?>" class="w-10 h-10 rounded-full object-cover border border-slate-200 dark:border-slate-700">
                <div class="overflow-hidden">
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></div>
                </div>
            </div>
            <a href="logout.php" class="w-full py-2 px-3 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 font-bold rounded-xl text-xs transition flex items-center justify-center gap-2">
                <i class="fas fa-sign-out-alt"></i> Keluar (Logout)
            </a>
        </div>
        <?php endif; ?>
    </aside>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php display_flash(); ?>

    <script>
    function toggleSidebar() {
        const drawer = document.getElementById('sidebarDrawer');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (drawer.classList.contains('-translate-x-full')) {
            openSidebar();
        } else {
            closeSidebar();
        }
    }

    function openSidebar() {
        const drawer = document.getElementById('sidebarDrawer');
        const backdrop = document.getElementById('sidebarBackdrop');
        backdrop.classList.remove('hidden');
        setTimeout(() => backdrop.classList.remove('opacity-0'), 10);
        drawer.classList.remove('-translate-x-full');
    }

    function closeSidebar() {
        const drawer = document.getElementById('sidebarDrawer');
        const backdrop = document.getElementById('sidebarBackdrop');
        drawer.classList.add('-translate-x-full');
        backdrop.classList.add('opacity-0');
        setTimeout(() => backdrop.classList.add('hidden'), 300);
    }

    function toggleTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateToggleUI();
    }

    function updateToggleUI() {
        const isDark = document.documentElement.classList.contains('dark');
        const knobs = document.querySelectorAll('.theme-toggle-knob');
        const switches = document.querySelectorAll('.theme-toggle-switch');
        const icons = document.querySelectorAll('.theme-toggle-icon');
        const texts = document.querySelectorAll('.theme-toggle-text');

        knobs.forEach(knob => {
            if (isDark) {
                knob.classList.remove('translate-x-0');
                knob.classList.add('translate-x-5');
            } else {
                knob.classList.remove('translate-x-5');
                knob.classList.add('translate-x-0');
            }
        });

        switches.forEach(sw => {
            if (isDark) {
                sw.classList.remove('bg-slate-300');
                sw.classList.add('bg-brand-600');
                sw.setAttribute('aria-checked', 'true');
            } else {
                sw.classList.remove('bg-brand-600');
                sw.classList.add('bg-slate-300');
                sw.setAttribute('aria-checked', 'false');
            }
        });

        icons.forEach(icon => {
            if (isDark) {
                icon.className = 'theme-toggle-icon fas fa-moon text-amber-400';
            } else {
                icon.className = 'theme-toggle-icon fas fa-sun text-amber-300';
            }
        });

        texts.forEach(text => {
            text.textContent = isDark ? 'Mode Terang' : 'Mode Gelap';
        });
    }

    document.addEventListener('DOMContentLoaded', updateToggleUI);

    <?php if (is_logged_in() && is_admin()): ?>
    const adminNotificationStorageKey = 'adminNotificationUnreadCount_<?php echo (int)$_SESSION['user_id']; ?>';
    let adminNotificationAudioContext = null;

    function prepareAdminNotificationAudio() {
        if (adminNotificationAudioContext) return;
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (AudioContextClass) {
            adminNotificationAudioContext = new AudioContextClass();
        }
    }

    function playAdminNotificationSound() {
        try {
            prepareAdminNotificationAudio();
            if (!adminNotificationAudioContext) return;

            if (adminNotificationAudioContext.state === 'suspended') {
                adminNotificationAudioContext.resume();
            }

            const now = adminNotificationAudioContext.currentTime;
            const oscillator = adminNotificationAudioContext.createOscillator();
            const gain = adminNotificationAudioContext.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, now);
            oscillator.frequency.setValueAtTime(1174.66, now + 0.12);
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(0.18, now + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.32);

            oscillator.connect(gain);
            gain.connect(adminNotificationAudioContext.destination);
            oscillator.start(now);
            oscillator.stop(now + 0.34);
        } catch (error) {
            // Kebijakan autoplay browser dapat menolak audio sebelum interaksi pengguna.
        }
    }

    function renderAdminNotificationCount(count, shouldPlaySound = true) {
        const badge = document.getElementById('adminNotificationBadge');
        const bellIcon = document.getElementById('adminNotificationBellIcon');
        if (!badge || !bellIcon) return;

        const normalizedCount = Math.max(0, Number.parseInt(count, 10) || 0);
        const previousRaw = sessionStorage.getItem(adminNotificationStorageKey);
        const previousCount = previousRaw === null ? null : Math.max(0, Number.parseInt(previousRaw, 10) || 0);

        if (normalizedCount > 0) {
            badge.textContent = normalizedCount > 99 ? '99+' : String(normalizedCount);
            badge.classList.remove('hidden');
        } else {
            badge.textContent = '0';
            badge.classList.add('hidden');
        }

        if (shouldPlaySound && previousCount !== null && normalizedCount > previousCount) {
            playAdminNotificationSound();
            bellIcon.classList.add('animate-bounce');
            window.setTimeout(() => bellIcon.classList.remove('animate-bounce'), 1200);
        }

        sessionStorage.setItem(adminNotificationStorageKey, String(normalizedCount));
    }

    async function pollAdminNotifications() {
        try {
            const response = await fetch('api/admin_notifications.php?t=' + Date.now(), {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (!response.ok) return;

            const data = await response.json();
            if (data.success) {
                renderAdminNotificationCount(data.unread_count);
            }
        } catch (error) {
            // Gangguan polling tidak boleh mengganggu halaman utama.
        }
    }

    async function openAdminNotifications() {
        prepareAdminNotificationAudio();

        try {
            const formData = new FormData();
            formData.append('action', 'mark_all_read');
            formData.append('csrf_token', <?php echo json_encode(csrf_token()); ?>);

            const response = await fetch('api/admin_notifications.php', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    renderAdminNotificationCount(0, false);
                }
            }
        } catch (error) {
            // Admin tetap diarahkan ke daftar booking jika penandaan gagal.
        }

        window.location.href = 'admin_bookings.php?status=pending';
    }

    document.addEventListener('pointerdown', prepareAdminNotificationAudio, { once: true });
    document.addEventListener('DOMContentLoaded', function() {
        pollAdminNotifications();
        window.setInterval(pollAdminNotifications, 20000);
    });
    <?php endif; ?>
    </script>
