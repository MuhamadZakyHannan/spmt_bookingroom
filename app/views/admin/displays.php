<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<!-- Admin Monitor Display Management View -->
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm transition-colors">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-brand-600 dark:text-brand-400 uppercase tracking-wider mb-1">
                <i class="fas fa-tv"></i> Digital Signage & Door Displays
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Kelola Monitor Ruangan</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">
                Atur konfigurasi monitor digital untuk setiap pintu ruang rapat tanpa perlu membuat akun terpisah.
            </p>
        </div>
        <div>
            <button onclick="document.getElementById('modalAddDisplay').classList.remove('hidden')" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold rounded-xl transition shadow-sm shadow-brand-500/20">
                <i class="fas fa-plus"></i> Daftarkan Monitor Baru
            </button>
        </div>
    </div>

    <?php display_flash(); ?>

    <?php if (!empty($error)): ?>
        <div class="p-4 bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-900 rounded-xl text-sm font-medium flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>


    <!-- Table of Displays -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm overflow-hidden transition-colors">
        <div class="p-5 border-b border-slate-100 dark:border-slate-700/80 flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-800 dark:text-slate-100">Daftar Monitor Ruangan Aktif (<?php echo count($displays); ?>)</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 uppercase text-[11px] font-bold tracking-wider border-b border-slate-100 dark:border-slate-700/80">
                    <tr>
                        <th class="py-3.5 px-5">Nama Monitor</th>
                        <th class="py-3.5 px-5">Ruangan Terkait</th>
                        <th class="py-3.5 px-5">Display Token</th>
                        <th class="py-3.5 px-5">Status Device</th>
                        <th class="py-3.5 px-5">URL Display</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/80 font-medium">
                    <?php if (empty($displays)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-400 dark:text-slate-500">
                                <i class="fas fa-tv text-3xl mb-2 block opacity-30"></i>
                                Belum ada monitor display yang didaftarkan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($displays as $disp): ?>
                            <?php 
                                $isOnline = !empty($disp['last_active']) && (time() - strtotime($disp['last_active']) <= 120);
                                $displayUrl = 'display.php?token=' . urlencode($disp['display_token']);
                            ?>
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition">
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400 flex items-center justify-center font-bold">
                                            <i class="fas fa-desktop"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($disp['display_name']); ?></div>
                                            <div class="text-[11px] text-slate-400 dark:text-slate-500">ID #<?php echo $disp['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="font-bold text-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($disp['room_name']); ?></div>
                                    <div class="text-xs text-slate-400 dark:text-slate-500 flex items-center gap-1.5 mt-0.5">
                                        <span class="px-1.5 py-0.2 bg-slate-100 dark:bg-slate-700 rounded text-[10px] font-mono font-bold text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($disp['room_code']); ?></span>
                                        <span><?php echo htmlspecialchars($disp['location']); ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-700/60 text-slate-800 dark:text-slate-200 font-mono text-xs font-bold border border-slate-200 dark:border-slate-600">
                                        <?php echo htmlspecialchars($disp['display_token']); ?>
                                    </span>
                                </td>
                                <td class="py-4 px-5">
                                    <?php if ($isOnline): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Online
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                            <span class="w-2 h-2 rounded-full bg-slate-400"></span> Siap / Standby
                                        </span>
                                    <?php endif; ?>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">
                                        <?php echo !empty($disp['last_active']) ? 'Aktif: ' . date('H:i, d M', strtotime($disp['last_active'])) : 'Belum pernah terhubung'; ?>
                                    </div>
                                </td>
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-2">
                                        <input type="text" readonly value="<?php echo htmlspecialchars($displayUrl); ?>" class="text-xs font-mono bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 px-2 py-1 rounded-lg w-44 select-all" id="urlInput_<?php echo $disp['id']; ?>">
                                        <button onclick="copyToClipboard('urlInput_<?php echo $disp['id']; ?>')" class="p-1.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 rounded-lg transition" title="Salin URL">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="py-4 px-5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="<?php echo htmlspecialchars($displayUrl); ?>" target="_blank" class="px-3 py-1.5 bg-brand-50 dark:bg-brand-900/40 hover:bg-brand-100 dark:hover:bg-brand-900/60 text-brand-700 dark:text-brand-300 rounded-xl text-xs font-bold transition flex items-center gap-1">
                                            <i class="fas fa-external-link-alt text-[10px]"></i> Buka Kiosk
                                        </a>
                                        
                                        <!-- Regenerate Token -->
                                        <form method="POST" action="admin_displays.php" onsubmit="return confirm('Regenerasi token baru untuk display ini? URL lama tidak akan bisa diakses lagi.')" class="inline">
                                            <input type="hidden" name="action" value="regenerate_token">
                                            <input type="hidden" name="display_id" value="<?php echo $disp['id']; ?>">
                                            <button type="submit" class="p-1.5 text-slate-400 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded-lg transition" title="Buat Token Baru">
                                                <i class="fas fa-sync-alt text-xs"></i>
                                            </button>
                                        </form>

                                        <!-- Delete -->
                                        <form method="POST" action="admin_displays.php" onsubmit="return confirm('Hapus konfigurasi display monitor ini?')" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="display_id" value="<?php echo $disp['id']; ?>">
                                            <button type="submit" class="p-1.5 text-slate-400 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition" title="Hapus Display">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add Display -->
<div id="modalAddDisplay" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 dark:border-slate-700 space-y-5 animate-in fade-in zoom-in duration-200 transition-colors">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-brand-50 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400 flex items-center justify-center font-bold">
                    <i class="fas fa-tv text-sm"></i>
                </div>
                <h3 class="font-bold text-slate-900 dark:text-white text-lg">Daftarkan Monitor Display</h3>
            </div>
            <button onclick="document.getElementById('modalAddDisplay').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="admin_displays.php" class="space-y-4">
            <input type="hidden" name="action" value="create">

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Pilih Ruangan <span class="text-rose-500">*</span></label>
                <select name="room_id" required class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="">-- Pilih Ruang Rapat --</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?php echo $r['id']; ?>">
                            <?php echo htmlspecialchars($r['name']); ?> (<?php echo htmlspecialchars($r['code']); ?>) - <?php echo htmlspecialchars($r['floor']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nama Perangkat / Display <span class="text-rose-500">*</span></label>
                <input type="text" name="display_name" required placeholder="Contoh: Display Tablet Pintu Alpha" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Custom Display Token (Opsional)</label>
                <input type="text" name="token" placeholder="Kosongkan untuk generate otomatis" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-mono text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">Jika dikosongkan, sistem akan otomatis menghasilkan token acak (misal: DISP-A1B2C3).</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-700">
                <button type="button" onclick="document.getElementById('modalAddDisplay').classList.add('hidden')" class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition shadow-sm">
                    Simpan Display
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const copyText = document.getElementById(elementId);
    const fullUrl = window.location.origin + window.location.pathname.replace('admin_displays.php', '') + copyText.value;
    navigator.clipboard.writeText(fullUrl).then(() => {
        alert('URL Monitor Display berhasil disalin ke clipboard:\n' + fullUrl);
    }).catch(() => {
        copyText.select();
        document.execCommand('copy');
        alert('URL disalin!');
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
