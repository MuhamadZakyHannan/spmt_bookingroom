<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Ruang Rapat - Lobby Utama</title>
    <!-- Tailwind CSS (Local Compiled Standalone) -->
    <link rel="stylesheet" href="public/css/tailwind.min.css">
    <script src="public/js/ui-utils.js?v=<?php echo asset_version('public/js/ui-utils.js'); ?>"></script>
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen sm:h-screen flex flex-col p-2.5 sm:p-4 lg:p-5 font-sans select-none overflow-x-hidden">

    <!-- Top Admin Quick Switcher Bar (HANYA MUNCUL KETIKA DILUAR FULLSCREEN) -->
    <div id="outsideFullscreenBar" class="max-w-[1400px] mx-auto w-full mb-2.5 flex flex-wrap items-center justify-between gap-2 bg-white border border-slate-200/90 px-3 py-2 rounded-xl text-xs text-slate-700 shadow-xs shrink-0">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-600 animate-pulse"></span>
            <span class="bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-0.5 rounded-full text-xs font-mono font-bold">
                MONITOR LOBBY UTAMA
            </span>
        </div>

        <div class="flex items-center gap-2">
            <a href="display.php" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition flex items-center gap-1.5 border border-slate-200">
                <i class="fas fa-tv text-xs text-slate-500"></i>
                <span>Monitor Pintu</span>
            </a>

            <button onclick="toggleFullScreen()" class="px-3 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold flex items-center gap-1.5 transition shadow-xs">
                <i class="fas fa-expand text-xs"></i>
                <span>Fullscreen</span>
            </button>

            <a href="dashboard.php" class="px-2.5 py-1 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 font-semibold transition">
                Dashboard
            </a>
        </div>
    </div>

    <!-- MODERN DIGITAL SIGNAGE CONTAINER -->
    <div id="monitorFrame" class="relative mx-auto max-w-[1400px] w-full flex-1 min-h-0 flex flex-col justify-between">
        
        <!-- Main Display Surface -->
        <div class="relative rounded-2xl sm:rounded-3xl bg-white p-4 sm:p-6 lg:p-7 shadow-md border border-slate-200/90 overflow-hidden text-slate-800 flex-1 min-h-0 flex flex-col justify-between gap-3 sm:gap-4">
          
            <!-- INNER SCREEN CONTAINER -->
            <div class="relative z-10 flex-1 min-h-0 flex flex-col justify-between gap-3 sm:gap-4">
            
                <!-- 1. TOP HEADER -->
                <div class="flex flex-col md:flex-row md:items-center justify-between pb-3 sm:pb-4 border-b border-slate-200 gap-2.5 shrink-0">
                    <!-- Left Title -->
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                            <span class="flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-300/80 text-[11px] font-bold tracking-wider uppercase shadow-xs">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 pulse-glow"></span>
                                LIVE MONITOR
                            </span>
                            <span id="liveDateStr" class="px-3 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold border border-slate-200 text-[11px]">
                                Hari Ini
                            </span>
                        </div>

                        <div class="flex items-center gap-2 pt-0.5">
                            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-slate-900 uppercase">
                                JADWAL RUANG RAPAT HARI INI
                            </h1>
                        </div>
                    </div>

                    <!-- Right Clock & Active Stats -->
                    <div class="flex items-center justify-between md:justify-end gap-4 sm:gap-6">
                        <div class="hidden sm:flex items-center gap-2.5">
                            <div class="px-3.5 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-right">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Jadwal Aktif</div>
                                <div id="totalActiveCountEl" class="font-bold text-sm sm:text-base text-slate-900"><?php echo $totalActiveSchedule; ?> Sesi</div>
                            </div>
                            <div id="berlangsungBox" class="px-3.5 py-1.5 rounded-xl bg-emerald-50 border border-emerald-300 text-right shadow-xs <?php echo $activeNowCount > 0 ? '' : 'hidden'; ?>">
                                <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Berlangsung</div>
                                <div id="berlangsungCountEl" class="font-extrabold text-sm sm:text-base text-emerald-900 animate-pulse"><?php echo $activeNowCount; ?> Sesi</div>
                            </div>
                        </div>

                        <!-- Jam Digital Bersih & Sangat Terang -->
                        <div class="text-right flex flex-col items-end justify-center">
                            <div id="bigClock" class="text-3xl sm:text-4xl lg:text-5xl font-mono font-black tracking-tight text-blue-700">
                                <?php echo date('H:i:s'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. FULL-WIDTH JADWAL LIST TABLE -->
                <div class="flex-1 min-h-0 flex flex-col justify-start overflow-hidden">
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs flex-1 min-h-0 flex flex-col justify-start">
                        
                        <!-- Table Container dengan overflow auto -->
                        <div class="w-full overflow-x-hidden overflow-y-auto flex-1 min-h-0">
                            <table class="w-full table-fixed border-collapse">
                                <colgroup>
                                    <col class="w-[5%]">
                                    <col class="w-[25%]">
                                    <col class="w-[27%]">
                                    <col class="w-[13%]">
                                    <col class="w-[13%]">
                                    <col class="w-[17%]">
                                </colgroup>
                                <thead class="bg-slate-100/90 sticky top-0 z-10 border-b border-slate-200">
                                    <tr class="text-xs font-mono font-bold uppercase tracking-wider text-slate-700">
                                        <th class="py-3 px-3 text-center">NO</th>
                                        <th class="py-3 px-3 text-left">RUANGAN & LOKASI</th>
                                        <th class="py-3 px-3 text-left">AGENDA PERTEMUAN</th>
                                        <th class="py-3 px-3 text-left">DIVISI</th>
                                        <th class="py-3 px-3 text-center">WAKTU (WIB)</th>
                                        <th class="py-3 px-3 text-right">STATUS</th>
                                    </tr>
                                </thead>
                                <tbody id="scheduleTableBody" class="divide-y divide-slate-100">
                                    <!-- Rows injected and updated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Initial Data for Immediate Zero-Lag Rendering -->
    <script>
        let currentBookingsData = <?php echo json_encode($activeBookings); ?>;
    </script>

    <!-- Seamless In-Place DOM Update Script (No Fullscreen Exit / No Page Reloads) -->
    <script>
        const escapeHtml = window.MeetSpaceUI.escapeHtml;

        /** Menampilkan atau menutup schedule rows. */
        function renderScheduleRows() {
            const tableBody = document.getElementById('scheduleTableBody');
            if (!tableBody) return;

            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const timeStr = `${hours}:${minutes}:${seconds}`;
            const currentHHMM = `${hours}:${minutes}`;

            // Update Big Clock & Date
            const bigClock = document.getElementById('bigClock');
            if (bigClock) bigClock.textContent = timeStr;

            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const dateStr = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
            const dateEl = document.getElementById('liveDateStr');
            if (dateEl) dateEl.textContent = dateStr;

            // Filter out finished bookings
            const activeList = (currentBookingsData || []).filter(b => {
                const end5 = (b.end_time || '').substring(0, 5);
                return currentHHMM < end5;
            });

            // Sort by start_time
            activeList.sort((a, b) => (a.start_time || '').localeCompare(b.start_time || ''));

            let activeNowCount = 0;

            if (activeList.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="py-10 sm:py-14 text-center">
                            <div class="flex flex-col items-center justify-center space-y-3">
                                <div class="w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl border border-emerald-200 shadow-xs mx-auto">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-base sm:text-lg text-slate-900">
                                        Tidak ada jadwal hari ini
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                let html = '';
                activeList.forEach((b, idx) => {
                    const start5 = (b.start_time || '').substring(0, 5);
                    const end5 = (b.end_time || '').substring(0, 5);
                    const isWithinSchedule = (currentHHMM >= start5 && currentHHMM < end5);
                    const isNow = isWithinSchedule;

                    if (isNow) activeNowCount++;

                    const rowBg = isNow
                        ? 'bg-emerald-100/90 border-l-[6px] border-emerald-500 shadow-sm'
                        : 'bg-white hover:bg-slate-50/90 border-l-[6px] border-slate-200';
                    const padIndex = String(idx + 1).padStart(2, '0');
                    const divisiName = b.user_dept || 'Divisi Operasional';

                    html += `
                        <tr class="transition duration-150 relative ${rowBg}">
                            <!-- No -->
                            <td class="py-4 px-3.5 text-center font-mono font-bold text-xs sm:text-sm align-middle">
                                <div class="flex items-center justify-center gap-2">
                                    ${isNow ? `
                                        <span class="relative flex h-3 w-3">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                        </span>
                                    ` : `
                                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                    `}
                                    <span class="${isNow ? 'text-emerald-950 font-black' : 'text-slate-500'}">${padIndex}</span>
                                </div>
                            </td>

                            <!-- Ruangan & Lokasi -->
                            <td class="py-4 px-3.5 text-left align-middle overflow-hidden">
                                <div class="font-bold text-xs sm:text-sm lg:text-base whitespace-normal break-words leading-tight ${isNow ? 'text-emerald-950 font-black' : 'text-slate-900'}">
                                    ${escapeHtml(b.room_name || 'Ruang Rapat')}
                                </div>
                                <div class="text-[11px] whitespace-normal break-words leading-tight mt-0.5 font-medium ${isNow ? 'text-emerald-800/90 font-semibold' : 'text-slate-500'}">
                                    ${escapeHtml(b.room_floor || 'Gedung Utama')} â€¢ Kap. ${escapeHtml(b.attendees_count || '10')} orang
                                </div>
                            </td>

                            <!-- Agenda Pertemuan (Judul Saja) -->
                            <td class="py-4 px-3.5 text-left align-middle overflow-hidden">
                                <div class="text-xs sm:text-sm lg:text-base whitespace-normal break-words leading-tight ${isNow ? 'text-emerald-950 font-black' : 'text-slate-900 font-bold'}" title="${escapeHtml(b.title)}">
                                    ${escapeHtml(b.title)}
                                </div>
                            </td>

                            <!-- Divisi Saja -->
                            <td class="py-4 px-3.5 text-left align-middle overflow-hidden">
                                <div class="text-xs sm:text-sm whitespace-normal break-words leading-tight ${isNow ? 'text-emerald-900 font-extrabold' : 'text-blue-700 font-bold'}">
                                    ${escapeHtml(divisiName)}
                                </div>
                            </td>

                            <!-- Waktu -->
                            <td class="py-4 px-3.5 text-center align-middle">
                                ${isNow ? `
                                    <span class="font-mono text-xs sm:text-sm px-3 py-1.5 rounded-lg border-2 border-emerald-400 bg-emerald-200 text-emerald-950 font-black whitespace-nowrap inline-block shadow-xs">
                                        ${start5} - ${end5}
                                    </span>
                                ` : `
                                    <span class="font-mono text-xs sm:text-sm px-3 py-1.5 rounded-lg border border-slate-300 bg-slate-100 text-slate-800 font-bold whitespace-nowrap inline-block">
                                        ${start5} - ${end5}
                                    </span>
                                `}
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-3.5 text-right align-middle">
                                ${isNow ? `
                                    <span class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg border-2 border-emerald-700 bg-emerald-600 text-white text-[11px] sm:text-xs font-black font-mono tracking-wide uppercase text-center leading-tight shadow-md animate-pulse">
                                        <span class="w-2 h-2 rounded-full bg-white"></span>
                                        SEDANG BERLANGSUNG
                                    </span>
                                ` : `
                                    <span class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg border-2 border-blue-700 bg-blue-600 text-white text-[11px] sm:text-xs font-black font-mono tracking-wide uppercase text-center leading-tight shadow-md">
                                        <span class="w-2 h-2 rounded-full bg-white"></span>
                                        TERJADWAL
                                    </span>
                                `}
                            </td>
                        </tr>
                    `;
                });
                tableBody.innerHTML = html;
            }

            // Update Counters
            const totalActiveEl = document.getElementById('totalActiveCountEl');
            if (totalActiveEl) totalActiveEl.textContent = activeList.length + ' Sesi';

            const berlangsungBox = document.getElementById('berlangsungBox');
            const berlangsungCountEl = document.getElementById('berlangsungCountEl');
            if (berlangsungBox && berlangsungCountEl) {
                if (activeNowCount > 0) {
                    berlangsungBox.classList.remove('hidden');
                    berlangsungCountEl.textContent = activeNowCount + ' Sesi';
                } else {
                    berlangsungBox.classList.add('hidden');
                }
            }
        }

        // Silent Background Fetching (TIDAK ADA RELOAD HALAMAN = 100% AMAN FULLSCREEN)
        /** Mengambil jadwal lobby terbaru tanpa memuat ulang display. */
        async function fetchLobbyDataSilently() {
            try {
                const res = await fetch('api/lobby_status.php?t=' + Date.now());
                if (res.ok) {
                    const data = await res.json();
                    if (data && data.success && Array.isArray(data.bookings)) {
                        currentBookingsData = data.bookings;
                        renderScheduleRows();
                    }
                }
            } catch (err) {
                // Silently ignore network errors in kiosk mode
            }
        }

        // Jalankan render setiap detik untuk jam & status
        setInterval(renderScheduleRows, 1000);
        renderScheduleRows();

        // Polling background data diam-diam setiap 5 detik tanpa merusak Fullscreen
        setInterval(fetchLobbyDataSilently, 5000);

        /** Mengaktifkan atau menutup mode layar penuh. */
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen().catch(() => {});
                }
            }
        }
    </script>
</body>
</html>
