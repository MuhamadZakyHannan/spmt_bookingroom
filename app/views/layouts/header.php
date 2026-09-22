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
    <link rel="stylesheet" href="public/css/tailwind.min.css?v=<?php echo asset_version('public/css/tailwind.min.css'); ?>">
    <script src="public/js/theme-init.js?v=<?php echo asset_version('public/js/theme-init.js'); ?>"></script>
    <script src="public/js/ui-utils.js?v=<?php echo asset_version('public/js/ui-utils.js'); ?>"></script>
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 font-sans min-h-screen flex flex-col antialiased transition-colors duration-200">
    <?php require __DIR__ . '/_runtime_config.php'; ?>

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
                                <?php if (is_super_admin()): ?>
                                    <span class="bg-violet-600 text-white font-bold text-[10px] px-1.5 py-0.5 rounded">SUPER ADMIN</span>
                                <?php elseif (is_admin()): ?>
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
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate"><?php echo htmlspecialchars($_SESSION['user_username'] ?? $_SESSION['user_email'] ?? ''); ?></p>
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
                        <?php if (APP_ALLOW_REGISTRATION): ?>
                            <a href="register.php" class="px-4 py-2 rounded-lg text-sm font-bold bg-sky-600 hover:bg-sky-700 text-white shadow transition">Daftar</a>
                        <?php endif; ?>
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
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate"><?php echo htmlspecialchars($_SESSION['user_username'] ?? $_SESSION['user_email'] ?? ''); ?></div>
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

    <script src="public/js/site-shell.js?v=<?php echo asset_version('public/js/site-shell.js'); ?>"></script>
