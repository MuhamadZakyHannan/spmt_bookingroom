<?php
// Pastikan zona waktu selalu WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

$currentTime = date('H:i');
$todayList = $liveData['today_schedule'] ?? [];

// Filter HANYA booking confirmed untuk ruangan ini yang MASIH BERLANGSUNG atau AKAN DATANG
$activeList = array_values(array_filter($todayList, function ($b) use ($currentTime) {
    if (($b['status'] ?? 'confirmed') !== 'confirmed') {
        return false;
    }
    $end5 = substr($b['end_time'], 0, 5);
    return $currentTime < $end5;
}));

// Hitung sesi aktif saat ini
$activeMeetingNow = null;
foreach ($activeList as $b) {
    $start5 = substr($b['start_time'], 0, 5);
    $end5 = substr($b['end_time'], 0, 5);
    if ($currentTime >= $start5 && $currentTime < $end5) {
        $activeMeetingNow = $b;
        break;
    }
}
$totalActiveSesi = count($activeList);
?>
<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($room['name']); ?> - Monitor Pintu</title>
    <!-- Tailwind CSS (Local Compiled Standalone) -->
    <link rel="stylesheet" href="public/css/tailwind.min.css">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        /* Hide scrollbars in kiosk mode */
        html,
        body {
            height: 100%;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        @keyframes pulse-glow {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.4;
                transform: scale(1.08);
            }
        }

        .pulse-glow {
            animation: pulse-glow 2s infinite ease-in-out;
        }

        /* When in fullscreen, hide any top controls */
        :fullscreen #outsideFullscreenBar {
            display: none !important;
        }

        :-webkit-full-screen #outsideFullscreenBar {
            display: none !important;
        }
    </style>
</head>

<body class="bg-slate-100 text-slate-800 min-h-screen sm:h-screen flex flex-col p-2.5 sm:p-4 lg:p-5 font-sans select-none overflow-x-hidden">

    <!-- Top Admin Quick Switcher Bar (HANYA MUNCUL KETIKA DILUAR FULLSCREEN) -->
    <div id="outsideFullscreenBar" class="max-w-[1720px] 2xl:max-w-[1850px] mx-auto w-full mb-2.5 flex flex-wrap items-center justify-between gap-2 bg-white border border-slate-200/90 px-3 py-2 rounded-xl text-xs text-slate-700 shadow-xs shrink-0">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-600 animate-pulse"></span>
            <span class="bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-0.5 rounded-full text-xs font-mono font-bold">
                MONITOR PINTU: <?php echo htmlspecialchars($room['name']); ?>
            </span>
        </div>

        <div class="flex items-center gap-2">
            <!-- Room Quick Selector (Hanya di luar fullscreen) -->
            <form method="GET" action="display.php" class="flex items-center gap-1.5 bg-slate-50 px-2.5 py-1 rounded-lg border border-slate-200">
                <span class="text-xs text-slate-500 font-medium">Pilih Ruangan:</span>
                <select name="room" onchange="this.form.submit()" class="bg-white border border-slate-300 text-blue-700 text-xs font-bold rounded px-2 py-0.5 focus:outline-none cursor-pointer">
                    <?php foreach ($allRooms as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php echo $r['id'] == $room['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <a href="display_lobby.php" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold transition flex items-center gap-1.5 border border-slate-200">
                <i class="fas fa-desktop text-xs text-slate-500"></i>
                <span>Monitor Lobby</span>
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
    <div id="monitorFrame" class="relative mx-auto max-w-[1720px] 2xl:max-w-[1850px] w-full flex-1 min-h-0 flex flex-col justify-between">

        <!-- Main Display Surface -->
        <div class="relative rounded-2xl sm:rounded-3xl bg-white p-4 sm:p-6 lg:p-7 shadow-md border border-slate-200/90 overflow-hidden text-slate-800 flex-1 min-h-0 flex flex-col justify-between gap-3 sm:gap-4">

            <!-- INNER SCREEN CONTAINER -->
            <div class="relative z-10 flex-1 min-h-0 flex flex-col justify-between gap-3 sm:gap-4">

                <!-- 1. TOP HEADER (High Contrast & Clear Room Identifier) -->
                <div class="flex flex-col md:flex-row md:items-center justify-between pb-3 sm:pb-4 border-b border-slate-200 gap-2.5 shrink-0">
                    <!-- Left Title & Room Info -->
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span id="liveDateStr" class="px-3.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold border border-slate-200 text-xs sm:text-sm">
                                Hari Ini
                            </span>
                        </div>
                        <!-- NAMA RUANGAN (Jelas, Bersih, Ringkas) -->
                        <div class="flex items-center gap-2 pt-0.5">
                            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-slate-900 uppercase">
                                <?php echo htmlspecialchars($room['name']); ?>
                            </h1>
                        </div>
                    </div>

                    <!-- Right Clock & Stats -->
                    <div class="flex items-center justify-between md:justify-end gap-4 sm:gap-6">
                        <div class="hidden sm:flex items-center gap-2.5">
                            <div class="px-3.5 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-right">
                                <div class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500">Jadwal Aktif Hari Ini</div>
                                <div id="totalActiveCountEl" class="font-extrabold text-sm sm:text-base lg:text-lg text-slate-900"><?php echo $totalActiveSesi; ?> Sesi</div>
                            </div>
                        </div>

                        <!-- Jam Digital Bersih & Terang -->
                        <div class="text-right flex flex-col items-end justify-center">
                            <div id="bigClock" class="text-3xl sm:text-4xl lg:text-5xl font-mono font-black tracking-tight text-blue-700">
                                <?php echo date('H:i:s'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. FULL-WIDTH JADWAL LIST TABLE -->
                <div class="flex-1 min-h-0 flex flex-col justify-start overflow-hidden">
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs flex-1 min-h-0 flex flex-col justify-start">

                        <!-- Table Container dengan overflow auto -->
                        <div class="w-full overflow-x-hidden overflow-y-auto flex-1 min-h-0">
                            <table class="w-full table-fixed border-collapse">
                                <colgroup>
                                    <col class="w-[6%]">
                                    <col class="w-[35%]">
                                    <col class="w-[18%]">
                                    <col class="w-[19%]">
                                    <col class="w-[22%]">
                                </colgroup>
                                <thead class="bg-slate-100/95 sticky top-0 z-10 border-b-2 border-slate-200">
                                    <tr class="text-xs sm:text-sm font-mono font-black uppercase tracking-wider text-slate-700">
                                        <th class="py-3.5 sm:py-4 px-4 text-center">NO</th>
                                        <th class="py-3.5 sm:py-4 px-4 text-left">AGENDA PERTEMUAN</th>
                                        <th class="py-3.5 sm:py-4 px-4 text-left">DIVISI</th>
                                        <th class="py-3.5 sm:py-4 px-4 text-center">WAKTU (WIB)</th>
                                        <th class="py-3.5 sm:py-4 px-5 sm:px-6 text-right">STATUS</th>
                                    </tr>
                                </thead>
                                <tbody id="scheduleTableBody" class="divide-y divide-slate-100">
                                    <!-- Injected dynamically without page reload -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Initial Data for Zero-Lag Immediate Display -->
    <script>
        const CURRENT_ROOM_ID = <?php echo (int)$room['id']; ?>;
        const CURRENT_ROOM_NAME = <?php echo json_encode($room['name']); ?>;
        const CURRENT_ROOM_FLOOR = <?php echo json_encode($room['floor'] ?? 'Lantai 1'); ?>;
        const CURRENT_ROOM_CAPACITY = <?php echo (int)($room['capacity'] ?? 10); ?>;
        const CURRENT_TOKEN = <?php echo json_encode($currentToken ?? ''); ?>;
        let currentRoomSchedule = <?php echo json_encode($activeList); ?>;
    </script>

    <!-- Seamless In-Place DOM Update Script (No Fullscreen Exit / No Page Reloads) -->
    <script>
        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderRoomSchedule() {
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
            const activeList = (currentRoomSchedule || []).filter(b => {
                if ((b.status || 'confirmed') !== 'confirmed') return false;
                const end5 = (b.end_time || '').substring(0, 5);
                return currentHHMM < end5;
            });

            // Sort by start_time
            activeList.sort((a, b) => (a.start_time || '').localeCompare(b.start_time || ''));

            let isRoomOccupied = false;

            if (activeList.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="py-20 sm:py-28 lg:py-36 text-center">
                            <div class="flex flex-col items-center justify-center space-y-4">
                                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-3xl sm:text-4xl border-2 border-emerald-200 shadow-sm mx-auto">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="space-y-1">
                                    <div class="font-black text-2xl sm:text-3xl lg:text-4xl text-slate-900">
                                        Tidak Ada Jadwal Hari Ini
                                    </div>
                                    <div class="text-sm sm:text-base lg:text-lg font-semibold text-slate-500">
                                        Ruangan saat ini kosong dan siap digunakan.
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                // Ukuran font dan spasi tabel rapi, proporsional, dan sejajar sempurna
                const padClass = 'py-6 sm:py-7 lg:py-8';
                const titleSize = 'text-lg sm:text-xl lg:text-2xl xl:text-3xl';
                const divisiSize = 'text-sm sm:text-base lg:text-lg xl:text-xl';
                const timeSize = 'text-sm sm:text-base lg:text-lg xl:text-xl px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl';
                const statusSize = 'text-xs sm:text-sm lg:text-base xl:text-lg px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-xl';
                const noSize = 'text-lg sm:text-xl lg:text-2xl';
                const dotSize = 'w-3 h-3';

                let html = '';
                activeList.forEach((b, idx) => {
                    const start5 = (b.start_time || '').substring(0, 5);
                    const end5 = (b.end_time || '').substring(0, 5);
                    const isNow = (currentHHMM >= start5 && currentHHMM < end5);

                    if (isNow) isRoomOccupied = true;

                    const rowBg = isNow ?
                        'bg-amber-100/90 shadow-2xs' :
                        'bg-white hover:bg-slate-50/90';
                    const padIndex = String(idx + 1).padStart(2, '0');
                    const divisiName = b.user_dept || 'Divisi Operasional';

                    html += `
                        <tr class="transition duration-150 relative ${rowBg}">
                            <!-- No -->
                            <td class="${padClass} px-4 text-center font-mono font-black ${noSize} align-middle relative">
                                <!-- Accent bar kiri rapi tanpa menggeser kolom -->
                                <div class="absolute left-0 top-0 bottom-0 w-2.5 ${isNow ? 'bg-amber-500' : 'bg-transparent'}"></div>
                                <div class="flex items-center justify-center gap-2 pl-1">
                                    ${isNow ? `
                                        <span class="relative flex ${dotSize}">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full ${dotSize} bg-amber-500"></span>
                                        </span>
                                    ` : `
                                        <span class="${dotSize} rounded-full bg-blue-500"></span>
                                    `}
                                    <span class="${isNow ? 'text-amber-950 font-black' : 'text-slate-600 font-extrabold'}">${padIndex}</span>
                                </div>
                            </td>

                            <!-- Agenda Pertemuan (Judul Saja) -->
                            <td class="${padClass} px-4 text-left align-middle overflow-hidden">
                                <div class="${titleSize} whitespace-normal break-words leading-tight sm:leading-snug font-black ${isNow ? 'text-amber-950' : 'text-slate-900'}" title="${escapeHtml(b.title)}">
                                    ${escapeHtml(b.title)}
                                </div>
                            </td>

                            <!-- Divisi Saja -->
                            <td class="${padClass} px-4 text-left align-middle overflow-hidden">
                                <div class="${divisiSize} whitespace-normal break-words leading-tight ${isNow ? 'text-amber-900 font-black' : 'text-blue-700 font-bold'}">
                                    ${escapeHtml(divisiName)}
                                </div>
                            </td>

                            <!-- Waktu -->
                            <td class="${padClass} px-4 text-center align-middle">
                                <div class="flex items-center justify-center">
                                    <span class="font-mono ${timeSize} border-2 ${isNow ? 'border-amber-400 bg-amber-200 text-amber-950 font-black' : 'border-slate-300 bg-slate-100 text-slate-800 font-bold'} whitespace-nowrap inline-flex items-center justify-center gap-2 shadow-2xs">
                                        <i class="far fa-clock opacity-80 text-xs sm:text-sm"></i>
                                        ${start5} - ${end5}
                                    </span>
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="${padClass} px-5 sm:px-6 text-right align-middle">
                                <div class="flex items-center justify-end">
                                    <span class="inline-flex items-center justify-center gap-2.5 ${statusSize} border-2 ${isNow ? 'border-red-700 bg-red-600 text-white animate-pulse' : 'border-blue-700 bg-blue-600 text-white'} font-black font-mono tracking-wider uppercase text-center leading-tight shadow-md whitespace-nowrap">
                                        <span class="w-2.5 h-2.5 rounded-full bg-white shrink-0"></span>
                                        ${isNow ? 'BERLANGSUNG' : 'TERJADWAL'}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                tableBody.innerHTML = html;
            }

            // Update Total Active Counter
            const totalActiveEl = document.getElementById('totalActiveCountEl');
            if (totalActiveEl) totalActiveEl.textContent = activeList.length + ' Sesi';

            // Update Room Status Indicator Box
            const roomBox = document.getElementById('roomStatusBox');
            const roomLabel = document.getElementById('roomStatusLabel');
            const roomValue = document.getElementById('roomStatusValue');

            if (roomBox && roomLabel && roomValue) {
                if (isRoomOccupied) {
                    roomBox.className = 'px-3.5 py-2 rounded-xl border text-right shadow-xs bg-amber-50 border-amber-300';
                    roomLabel.className = 'text-[10px] font-bold uppercase tracking-wider text-amber-700';
                    roomValue.className = 'font-extrabold text-base text-amber-900 animate-pulse';
                    roomValue.textContent = 'SEDANG DIGUNAKAN';
                } else {
                    roomBox.className = 'px-3.5 py-2 rounded-xl border text-right shadow-xs bg-emerald-50 border-emerald-300';
                    roomLabel.className = 'text-[10px] font-bold uppercase tracking-wider text-emerald-700';
                    roomValue.className = 'font-extrabold text-base text-emerald-900';
                    roomValue.textContent = 'TERSEDIA';
                }
            }
        }

        // Silent Background Fetching (TIDAK ADA RELOAD HALAMAN = 100% AMAN FULLSCREEN)
        async function fetchRoomDataSilently() {
            try {
                let url = `api/display_status.php?room=${CURRENT_ROOM_ID}&t=${Date.now()}`;
                if (CURRENT_TOKEN) {
                    url += `&token=${encodeURIComponent(CURRENT_TOKEN)}`;
                }
                const res = await fetch(url);
                if (res.ok) {
                    const json = await res.json();
                    if (json && json.success && json.data && Array.isArray(json.data.today_schedule)) {
                        currentRoomSchedule = json.data.today_schedule;
                        renderRoomSchedule();
                    }
                }
            } catch (err) {
                // Silently ignore network errors in kiosk mode
            }
        }

        // Jalankan render setiap detik untuk jam & status
        setInterval(renderRoomSchedule, 1000);
        renderRoomSchedule();

        // Polling background data diam-diam setiap 5 detik tanpa merusak Fullscreen
        setInterval(fetchRoomDataSilently, 5000);

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