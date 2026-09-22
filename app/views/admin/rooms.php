<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            <i class="text-amber-500"></i> Kelola Ruangan Rapat
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Tambah ruangan baru, upload foto, dan edit informasi kapasitas, lokasi, fasilitas, serta status.</p>
    </div>
    <div class="flex items-center gap-2.5">
        <span class="text-xs font-semibold px-3 py-2 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl border border-slate-200 dark:border-slate-600">
            Total: <strong id="roomDataCount"><?php echo count($rooms); ?></strong> Ruangan
        </span>
        <button onclick="toggleAddModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl shadow-md transition text-xs">
            <i class="fas fa-plus-circle"></i> Tambah Ruangan
        </button>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="p-4 mb-6 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-300 text-sm flex items-center gap-3">
        <i class="fas fa-exclamation-triangle text-rose-500 text-lg shrink-0"></i>
        <div class="flex-1"><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<!-- Instant Live Search & Status Filter Form -->
<form id="roomFilterForm" onsubmit="return false;" class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm mb-6 flex flex-col sm:flex-row gap-3">
    <div class="flex-1 relative" id="roomSearchContainer">
        <i class="fas fa-search absolute left-3.5 top-3 text-slate-400 text-xs"></i>
        <input 
            type="text" 
            id="roomSearchInput" 
            name="search" 
            autocomplete="off" 
            placeholder="Ketik untuk mencari nama ruangan, kode, lokasi, atau fasilitas..." 
            class="w-full pl-9 pr-8 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition"
        >
        <button 
            type="button" 
            id="clearRoomSearchBtn" 
            onclick="clearRoomSearch()" 
            class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hidden text-xs transition" 
            title="Hapus pencarian"
        >
            <i class="fas fa-times"></i>
        </button>

        <!-- Floating Recommendations Dropdown Box -->
        <div id="roomSuggestionsBox" class="absolute left-0 right-0 top-full mt-1.5 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 py-1.5 z-50 hidden max-h-64 overflow-y-auto"></div>
    </div>

    <div class="w-full sm:w-48">
        <select id="roomStatusFilter" name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer">
            <option value="">-- Semua Status --</option>
            <option value="available">✓ Tersedia</option>
            <option value="occupied">⏳ Terpakai</option>
            <option value="maintenance">🛠️ Perawatan</option>
        </select>
    </div>

    <button type="button" onclick="applyLiveRoomFilter()" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-sm shrink-0">
        <i class="fas fa-search text-xs"></i>
        <span>Cari</span>
    </button>
</form>

<!-- ==========================================
     MODAL TAMBAH RUANGAN (DENGAN UPLOAD FOTO)
     ========================================== -->
<div id="addModal" class="responsive-modal fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="responsive-modal-panel bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 max-w-xl w-full overflow-hidden flex flex-col">
        <div class="p-5 bg-amber-600 text-white flex items-center justify-between shrink-0">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i class="fas fa-plus-circle"></i> Tambah Ruangan Rapat Baru
            </h3>
            <button onclick="toggleAddModal()" class="text-white/80 hover:text-white p-1 rounded-lg">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <form method="POST" action="admin_rooms.php" enctype="multipart/form-data" class="p-4 sm:p-6 space-y-4 overflow-y-auto">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="add">

            <div class="responsive-modal-grid gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kode Ruangan *</label>
                    <input type="text" name="code" placeholder="Misal: R-101" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-mono uppercase focus:outline-none focus:ring-2 focus:ring-amber-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kapasitas (Orang) *</label>
                    <input type="number" name="capacity" min="1" placeholder="10" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-amber-500" required>
                </div>
            </div>

            <div class="responsive-modal-grid gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Ruangan *</label>
                    <input type="text" name="name" placeholder="Misal: Ruang Rapat Samudera" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-amber-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Status Awal</label>
                    <select name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer">
                        <option value="available">✓ Tersedia</option>
                        <option value="occupied">⏳ Terpakai</option>
                        <option value="maintenance">🛠️ Perawatan</option>
                    </select>
                </div>
            </div>

            <div class="responsive-modal-grid gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Lokasi Gedung *</label>
                    <input type="text" name="location" placeholder="Gedung Pelindo" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-amber-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Lantai</label>
                    <input type="text" name="floor" placeholder="Lantai 2" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Fasilitas (Dipisah Koma)</label>
                <input type="text" name="facilities" placeholder="Smart TV 4K, Proyektor, Video Conference, Whiteboard" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>

            <!-- Upload Foto Ruangan Langsung -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Upload Foto Ruangan</label>
                <div class="flex flex-col items-stretch gap-3 min-[420px]:flex-row min-[420px]:items-center min-[420px]:gap-4">
                    <div class="w-20 h-20 rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 overflow-hidden flex items-center justify-center shrink-0">
                        <img id="addPhotoPreview" src="public/rooms/KalTim.jpeg" class="w-full h-full object-cover" alt="Preview Foto">
                    </div>
                    <div class="flex-1">
                        <input 
                            type="file" 
                            name="room_image" 
                            id="addRoomImage" 
                            accept="image/png, image/jpeg, image/jpg, image/webp" 
                            onchange="previewImage(this, 'addPhotoPreview')" 
                            class="block w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-700 dark:file:bg-amber-950 dark:file:text-amber-300 hover:file:bg-amber-100 cursor-pointer"
                        >
                        <p class="text-[10px] text-slate-400 mt-1">Format didukung: JPG, PNG, WEBP (Maksimal 5MB).</p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Deskripsi Ruangan</label>
                <textarea name="description" rows="2" placeholder="Deskripsi tata ruang, peruntukan rapat, atau informasi teknis lainnya..." class="w-full p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
            </div>

            <div class="flex flex-col-reverse gap-2 pt-3 border-t border-slate-100 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" onclick="toggleAddModal()" class="w-full px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-lg text-xs sm:w-auto">Batal</button>
                <button type="submit" class="w-full px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg text-xs shadow sm:w-auto">Simpan Ruangan</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     MODAL EDIT INFORMASI & FOTO RUANGAN
     ========================================== -->
<div id="editModal" class="responsive-modal fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="responsive-modal-panel bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 max-w-xl w-full overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-150">
        <div class="p-5 bg-brand-600 text-white flex items-center justify-between shrink-0">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i class="fas fa-edit"></i> Edit Informasi & Fasilitas Ruangan
            </h3>
            <button onclick="closeEditModal()" class="text-white/80 hover:text-white p-1 rounded-lg">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <form method="POST" action="admin_rooms.php" enctype="multipart/form-data" class="p-4 sm:p-6 space-y-4 overflow-y-auto">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="room_id" id="editRoomId">

            <div class="responsive-modal-grid gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kode Ruangan *</label>
                    <input type="text" name="code" id="editCode" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-mono uppercase focus:outline-none focus:ring-2 focus:ring-brand-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kapasitas (Orang) *</label>
                    <input type="number" name="capacity" id="editCapacity" min="1" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-brand-500" required>
                </div>
            </div>

            <div class="responsive-modal-grid gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Ruangan *</label>
                    <input type="text" name="name" id="editName" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-brand-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Status Ruangan *</label>
                    <select name="status" id="editStatus" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer">
                        <option value="available">✓ Tersedia</option>
                        <option value="occupied">⏳ Terpakai</option>
                        <option value="maintenance">🛠️ Perawatan</option>
                    </select>
                </div>
            </div>

            <div class="responsive-modal-grid gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Lokasi Gedung *</label>
                    <input type="text" name="location" id="editLocation" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-brand-500" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Lantai</label>
                    <input type="text" name="floor" id="editFloor" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Fasilitas (Dipisah Koma)</label>
                <input type="text" name="facilities" id="editFacilities" placeholder="Smart TV 4K, Proyektor, Video Conference..." class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>

            <!-- Upload Foto Baru / Ganti Foto -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Foto Ruangan</label>
                <div class="flex flex-col items-stretch gap-3 min-[420px]:flex-row min-[420px]:items-center min-[420px]:gap-4">
                    <div class="w-20 h-20 rounded-xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 overflow-hidden flex items-center justify-center shrink-0">
                        <img id="editPhotoPreview" src="public/rooms/KalTim.jpeg" class="w-full h-full object-cover" alt="Preview Foto">
                    </div>
                    <div class="flex-1">
                        <input 
                            type="file" 
                            name="room_image" 
                            id="editRoomImage" 
                            accept="image/png, image/jpeg, image/jpg, image/webp" 
                            onchange="previewImage(this, 'editPhotoPreview')" 
                            class="block w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-50 file:text-brand-700 dark:file:bg-brand-950 dark:file:text-brand-300 hover:file:bg-brand-100 cursor-pointer"
                        >
                        <p class="text-[10px] text-slate-400 mt-1">Pilih file gambar baru untuk mengganti foto saat ini (Biarkan kosong jika tidak ingin mengganti).</p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Deskripsi Ruangan</label>
                <textarea name="description" id="editDescription" rows="2" class="w-full p-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
            </div>

            <div class="flex flex-col-reverse gap-2 pt-3 border-t border-slate-100 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" onclick="closeEditModal()" class="w-full px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-lg text-xs sm:w-auto">Batal</button>
                <button type="submit" class="w-full px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg text-xs shadow sm:w-auto">Perbarui Ruangan</button>
            </div>
        </form>
    </div>
</div>

<!-- ==========================================
     TABEL DAFTAR RUANGAN RAPAT
     ========================================== -->
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm overflow-hidden mb-8">
    <div class="responsive-table-shell">
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="bg-slate-50/80 dark:bg-slate-900/60 border-b border-slate-200/80 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                    <th class="py-3.5 px-4">Ruangan</th>
                    <th class="py-3.5 px-4">Kapasitas</th>
                    <th class="py-3.5 px-4">Lokasi & Lantai</th>
                    <th class="py-3.5 px-4">Fasilitas</th>
                    <th class="py-3.5 px-4">Status</th>
                    <th class="py-3.5 px-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody id="roomsTableBody" class="divide-y divide-slate-100 dark:divide-slate-700/80">
                <?php if (empty($rooms)): ?>
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">
                            <i class="fas fa-door-closed text-3xl mb-2 block"></i>
                            Tidak ada data ruangan rapat terdaftar.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($rooms as $r): 
                    $roomSlot = 'public/rooms/KalTim.jpeg';
                    $imgSrc = $r['image'] ?: $roomSlot;

                    $roomPayload = [
                        'id' => (int)$r['id'],
                        'code' => $r['code'],
                        'name' => $r['name'],
                        'capacity' => (int)$r['capacity'],
                        'location' => $r['location'],
                        'floor' => $r['floor'],
                        'facilities' => $r['facilities'],
                        'description' => $r['description'],
                        'status' => $r['status'],
                        'image' => $imgSrc
                    ];
                ?>
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition">
                        <!-- Ruangan (Foto + Nama + Kode) -->
                        <td class="py-3.5 px-4 flex items-center gap-3">
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" onerror="this.onerror=null; this.src='public/rooms/KalTim.jpeg';" class="w-12 h-12 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shadow-sm shrink-0">
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white text-sm"><?php echo htmlspecialchars($r['name']); ?></div>
                                <div class="text-[10px] font-mono text-brand-600 dark:text-brand-400 font-bold">[<?php echo htmlspecialchars($r['code']); ?>]</div>
                            </div>
                        </td>

                        <!-- Kapasitas -->
                        <td class="py-3.5 px-4 whitespace-nowrap font-bold text-slate-800 dark:text-slate-200">
                            <i class="fas fa-users text-slate-400 mr-1"></i> <?php echo $r['capacity']; ?> Orang
                        </td>

                        <!-- Lokasi & Lantai -->
                        <td class="py-3.5 px-4 whitespace-nowrap text-slate-600 dark:text-slate-300">
                            <div class="font-medium text-slate-800 dark:text-slate-200"><i class="fas fa-map-marker-alt text-rose-500 mr-1 text-[10px]"></i><?php echo htmlspecialchars($r['location']); ?></div>
                            <div class="text-[10px] text-slate-400 mt-0.5"><?php echo htmlspecialchars($r['floor'] ?: 'Lantai Dasar'); ?></div>
                        </td>

                        <!-- Fasilitas -->
                        <td class="py-3.5 px-4 max-w-xs">
                            <div class="text-slate-600 dark:text-slate-300 truncate text-[11px]" title="<?php echo htmlspecialchars($r['facilities']); ?>">
                                <?php echo htmlspecialchars($r['facilities'] ?: '-'); ?>
                            </div>
                        </td>

                        <!-- Status -->
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <form method="POST" action="admin_rooms.php" class="inline-block">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="room_id" value="<?php echo $r['id']; ?>">
                                <select name="status" onchange="this.form.submit()" class="px-2.5 py-1 text-[10px] font-bold rounded-lg border cursor-pointer focus:outline-none transition shadow-sm <?php 
                                    if ($r['status'] === 'available') echo 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800';
                                    else if ($r['status'] === 'occupied') echo 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800';
                                    else echo 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600';
                                ?>">
                                    <option value="available" <?php echo $r['status'] === 'available' ? 'selected' : ''; ?>>✓ Tersedia</option>
                                    <option value="occupied" <?php echo $r['status'] === 'occupied' ? 'selected' : ''; ?>>⏳ Terpakai</option>
                                    <option value="maintenance" <?php echo $r['status'] === 'maintenance' ? 'selected' : ''; ?>>🛠️ Perawatan</option>
                                </select>
                            </form>
                        </td>

                        <!-- Aksi (Edit & Hapus) -->
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1.5">
                                <button 
                                    type="button" 
                                    onclick="openEditModal(<?php echo htmlspecialchars(json_encode($roomPayload)); ?>)"
                                    class="px-2.5 py-1.5 bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/50 dark:hover:bg-brand-900/60 text-brand-600 dark:text-brand-400 border border-brand-200 dark:border-brand-800 rounded-lg transition text-xs font-bold inline-flex items-center gap-1 shadow-sm"
                                    title="Edit Informasi Ruangan"
                                >
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                
                                <form method="POST" action="admin_rooms.php" class="inline-block" onsubmit="return confirm('Hapus ruangan ini beserta histori jadwalnya?')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="room_id" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="p-1.5 px-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition" title="Hapus Ruangan">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const rawAdminRooms = <?php echo json_encode(array_map(function($r) {
        $roomSlot = 'public/rooms/KalTim.jpeg';
        return [
            'id' => (int)$r['id'],
            'code' => $r['code'],
            'name' => $r['name'],
            'capacity' => (int)$r['capacity'],
            'location' => $r['location'],
            'floor' => $r['floor'] ?: 'Lantai Dasar',
            'facilities' => $r['facilities'] ?: '-',
            'description' => $r['description'] ?: '',
            'status' => $r['status'],
            'image' => $r['image'] ?: $roomSlot
        ];
    }, $rooms)); ?>;

    const csrfHiddenField = '<?php echo addslashes(csrf_field()); ?>';
    const roomSearchInput = document.getElementById('roomSearchInput');
    const clearRoomSearchBtn = document.getElementById('clearRoomSearchBtn');
    const roomSuggestionsBox = document.getElementById('roomSuggestionsBox');
    const roomStatusFilter = document.getElementById('roomStatusFilter');
    const roomDataCount = document.getElementById('roomDataCount');
    const roomsTableBody = document.getElementById('roomsTableBody');

    function toggleAddModal() {
        const modal = document.getElementById('addModal');
        modal.classList.toggle('hidden');
        modal.classList.toggle('flex');
    }

    function openEditModal(room) {
        document.getElementById('editRoomId').value = room.id;
        document.getElementById('editCode').value = room.code;
        document.getElementById('editName').value = room.name;
        document.getElementById('editCapacity').value = room.capacity;
        document.getElementById('editLocation').value = room.location;
        document.getElementById('editFloor').value = room.floor || '';
        document.getElementById('editFacilities').value = room.facilities || '';
        document.getElementById('editDescription').value = room.description || '';
        document.getElementById('editStatus').value = room.status || 'available';
        
        const preview = document.getElementById('editPhotoPreview');
        preview.src = room.image || 'public/rooms/KalTim.jpeg';

        const modal = document.getElementById('editModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditModal() {
        const modal = document.getElementById('editModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    const { escapeHtml, highlightText } = window.MeetSpaceUI;

    function clearRoomSearch() {
        roomSearchInput.value = '';
        clearRoomSearchBtn.classList.add('hidden');
        roomSuggestionsBox.classList.add('hidden');
        applyLiveRoomFilter();
        roomSearchInput.focus();
    }

    function selectRoomSuggestion(value) {
        roomSearchInput.value = value;
        roomSuggestionsBox.classList.add('hidden');
        clearRoomSearchBtn.classList.remove('hidden');
        applyLiveRoomFilter();
    }

    function updateRoomSuggestions(query) {
        if (!query || query.length < 1) {
            roomSuggestionsBox.innerHTML = '';
            roomSuggestionsBox.classList.add('hidden');
            return;
        }

        const q = query.toLowerCase();
        const suggestions = [];
        const seen = new Set();

        rawAdminRooms.forEach(r => {
            if (r.name && r.name.toLowerCase().includes(q) && !seen.has('name:' + r.name)) {
                seen.add('name:' + r.name);
                suggestions.push({ type: 'Ruangan', text: r.name, icon: 'fa-door-open text-amber-500' });
            }
            if (r.code && r.code.toLowerCase().includes(q) && !seen.has('code:' + r.code)) {
                seen.add('code:' + r.code);
                suggestions.push({ type: 'Kode', text: r.code, icon: 'fa-tag text-blue-500' });
            }
            if (r.location && r.location.toLowerCase().includes(q) && !seen.has('loc:' + r.location)) {
                seen.add('loc:' + r.location);
                suggestions.push({ type: 'Lokasi', text: r.location, icon: 'fa-map-marker-alt text-rose-500' });
            }
        });

        if (suggestions.length === 0) {
            roomSuggestionsBox.innerHTML = `
                <div class="px-3.5 py-2 text-[11px] text-slate-400 text-center">
                    Tidak ditemukan saran untuk "<strong>${escapeHtml(query)}</strong>"
                </div>
            `;
            roomSuggestionsBox.classList.remove('hidden');
            return;
        }

        const topSuggestions = suggestions.slice(0, 6);
        let html = `
            <div class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-700/80 mb-1 flex items-center justify-between">
                <span>Rekomendasi Ruangan</span>
                <span class="text-[9px] font-normal text-slate-400">Klik untuk memilih</span>
            </div>
        `;

        topSuggestions.forEach(item => {
            const escapedVal = escapeHtml(item.text);
            const highlightedVal = highlightText(item.text, query);
            html += `
                <button 
                    type="button" 
                    onmousedown="selectRoomSuggestion('${escapedVal.replace(/'/g, "\\'")}')" 
                    class="w-full text-left px-3.5 py-2 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition flex items-center justify-between gap-2 text-xs"
                >
                    <div class="flex items-center gap-2 truncate">
                        <i class="fas ${item.icon} text-xs shrink-0"></i>
                        <span class="truncate text-slate-800 dark:text-slate-200 font-medium">${highlightedVal}</span>
                    </div>
                    <span class="text-[9px] font-semibold px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 rounded shrink-0">
                        ${item.type}
                    </span>
                </button>
            `;
        });

        roomSuggestionsBox.innerHTML = html;
        roomSuggestionsBox.classList.remove('hidden');
    }

    function applyLiveRoomFilter() {
        const query = roomSearchInput.value.trim();
        const q = query.toLowerCase();
        const selectedStatus = roomStatusFilter.value;

        if (query) {
            clearRoomSearchBtn.classList.remove('hidden');
        } else {
            clearRoomSearchBtn.classList.add('hidden');
        }

        let filtered = rawAdminRooms.filter(r => {
            if (selectedStatus && r.status !== selectedStatus) return false;
            if (!q) return true;

            const name = (r.name || '').toLowerCase();
            const code = (r.code || '').toLowerCase();
            const location = (r.location || '').toLowerCase();
            const floor = (r.floor || '').toLowerCase();
            const facilities = (r.facilities || '').toLowerCase();

            return name.includes(q) || code.includes(q) || location.includes(q) || floor.includes(q) || facilities.includes(q);
        });

        // Priority Sorting: Direct name / code matches float to top
        if (q) {
            filtered.sort((a, b) => {
                const aName = (a.name || '').toLowerCase();
                const aCode = (a.code || '').toLowerCase();
                const bName = (b.name || '').toLowerCase();
                const bCode = (b.code || '').toLowerCase();

                let aScore = 0;
                let bScore = 0;

                if (aName.startsWith(q) || aCode.startsWith(q)) aScore += 100;
                else if (aName.includes(q) || aCode.includes(q)) aScore += 75;
                if ((a.location || '').toLowerCase().includes(q)) aScore += 40;

                if (bName.startsWith(q) || bCode.startsWith(q)) bScore += 100;
                else if (bName.includes(q) || bCode.includes(q)) bScore += 75;
                if ((b.location || '').toLowerCase().includes(q)) bScore += 40;

                if (bScore !== aScore) return bScore - aScore;
                return a.id - b.id;
            });
        }

        if (roomDataCount) {
            roomDataCount.textContent = filtered.length;
        }

        renderRoomsTable(filtered, query);
    }

    function renderRoomsTable(rooms, highlightQuery = '') {
        if (!rooms || rooms.length === 0) {
            roomsTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="py-12 text-center text-slate-400">
                        <i class="fas fa-door-closed text-3xl mb-2 block"></i>
                        Tidak ada data ruangan yang sesuai dengan kriteria pencarian.
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        rooms.forEach(r => {
            const displayName = highlightText(r.name, highlightQuery);
            const displayCode = highlightText(r.code, highlightQuery);
            const displayLocation = highlightText(r.location, highlightQuery);
            const displayFloor = highlightText(r.floor, highlightQuery);
            const displayFacilities = highlightText(r.facilities, highlightQuery);

            const payloadData = escapeHtml(JSON.stringify(r));

            let statusSelectClass = '';
            if (r.status === 'available') statusSelectClass = 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800';
            else if (r.status === 'occupied') statusSelectClass = 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800';
            else statusSelectClass = 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600';

            html += `
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition">
                    <td class="py-3.5 px-4 flex items-center gap-3">
                        <img src="${escapeHtml(r.image)}" onerror="this.onerror=null; this.src='public/rooms/KalTim.jpeg';" class="w-12 h-12 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shadow-sm shrink-0">
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white text-sm">${displayName}</div>
                            <div class="text-[10px] font-mono text-brand-600 dark:text-brand-400 font-bold">[${displayCode}]</div>
                        </div>
                    </td>
                    <td class="py-3.5 px-4 whitespace-nowrap font-bold text-slate-800 dark:text-slate-200">
                        <i class="fas fa-users text-slate-400 mr-1"></i> ${r.capacity} Orang
                    </td>
                    <td class="py-3.5 px-4 whitespace-nowrap text-slate-600 dark:text-slate-300">
                        <div class="font-medium text-slate-800 dark:text-slate-200"><i class="fas fa-map-marker-alt text-rose-500 mr-1 text-[10px]"></i>${displayLocation}</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">${displayFloor}</div>
                    </td>
                    <td class="py-3.5 px-4 max-w-xs">
                        <div class="text-slate-600 dark:text-slate-300 truncate text-[11px]" title="${escapeHtml(r.facilities)}">
                            ${displayFacilities}
                        </div>
                    </td>
                    <td class="py-3.5 px-4 whitespace-nowrap">
                        <form method="POST" action="admin_rooms.php" class="inline-block">
                            ${csrfHiddenField}
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="room_id" value="${r.id}">
                            <select name="status" onchange="this.form.submit()" class="px-2.5 py-1 text-[10px] font-bold rounded-lg border cursor-pointer focus:outline-none transition shadow-sm ${statusSelectClass}">
                                <option value="available" ${r.status === 'available' ? 'selected' : ''}>✓ Tersedia</option>
                                <option value="occupied" ${r.status === 'occupied' ? 'selected' : ''}>⏳ Terpakai</option>
                                <option value="maintenance" ${r.status === 'maintenance' ? 'selected' : ''}>🛠️ Perawatan</option>
                            </select>
                        </form>
                    </td>
                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1.5">
                            <button 
                                type="button" 
                                onclick="openEditModal(${payloadData})"
                                class="px-2.5 py-1.5 bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/50 dark:hover:bg-brand-900/60 text-brand-600 dark:text-brand-400 border border-brand-200 dark:border-brand-800 rounded-lg transition text-xs font-bold inline-flex items-center gap-1 shadow-sm"
                                title="Edit Informasi Ruangan"
                            >
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            
                            <form method="POST" action="admin_rooms.php" class="inline-block" onsubmit="return confirm('Hapus ruangan ini beserta histori jadwalnya?')">
                                ${csrfHiddenField}
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="room_id" value="${r.id}">
                                <button type="submit" class="p-1.5 px-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition" title="Hapus Ruangan">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            `;
        });

        roomsTableBody.innerHTML = html;
    }

    document.addEventListener('DOMContentLoaded', () => {
        roomSearchInput.addEventListener('input', () => {
            const q = roomSearchInput.value.trim();
            updateRoomSuggestions(q);
            applyLiveRoomFilter();
        });

        roomSearchInput.addEventListener('focus', () => {
            const q = roomSearchInput.value.trim();
            if (q) updateRoomSuggestions(q);
        });

        roomStatusFilter.addEventListener('change', applyLiveRoomFilter);

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#roomSearchContainer')) {
                roomSuggestionsBox.classList.add('hidden');
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
