<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
            <i class="fas fa-users text-amber-500"></i> Kelola Pengguna Aplikasi
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Kelola hak akses pengguna aplikasi (Role: Administrator atau User).</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="text-xs font-semibold px-3 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl border border-slate-200 dark:border-slate-600">
            Total: <strong id="userDataCount"><?php echo count($users); ?></strong> Pengguna
        </span>
    </div>
</div>

<!-- Instant Live Search & Role Filter Form -->
<form id="userFilterForm" onsubmit="return false;" class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm mb-6 flex flex-col sm:flex-row gap-3">
    <div class="flex-1 relative" id="userSearchContainer">
        <i class="fas fa-search absolute left-3.5 top-3 text-slate-400 text-xs"></i>
        <input 
            type="text" 
            id="userSearchInput" 
            name="search" 
            autocomplete="off" 
            placeholder="Ketik untuk mencari nama pengguna atau email..." 
            class="w-full pl-9 pr-8 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 transition"
        >
        <button 
            type="button" 
            id="clearUserSearchBtn" 
            onclick="clearUserSearch()" 
            class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hidden text-xs transition" 
            title="Hapus pencarian"
        >
            <i class="fas fa-times"></i>
        </button>

        <!-- Floating Recommendations Dropdown Box -->
        <div id="userSuggestionsBox" class="absolute left-0 right-0 top-full mt-1.5 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 py-1.5 z-50 hidden max-h-64 overflow-y-auto"></div>
    </div>

    <div class="w-full sm:w-48">
        <select id="userRoleFilter" name="role" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer">
            <option value="">-- Semua Role --</option>
            <option value="super_admin">SUPER ADMIN</option>
            <option value="admin">ADMIN</option>
            <option value="user">USER</option>
        </select>
    </div>

    <button type="button" onclick="applyLiveUserFilter()" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-sm shrink-0">
        <i class="fas fa-search text-xs"></i>
        <span>Cari</span>
    </button>
</form>

<!-- Table of Users -->
<div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/80 shadow-sm overflow-hidden mb-8">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="bg-slate-50/80 dark:bg-slate-900/60 border-b border-slate-200/80 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                    <th class="py-3.5 px-4">Pengguna</th>
                    <th class="py-3.5 px-4">Email</th>
                    <th class="py-3.5 px-4">Role Hak Akses</th>
                    <th class="py-3.5 px-4">Tanggal Terdaftar</th>
                    <th class="py-3.5 px-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody id="usersTableBody" class="divide-y divide-slate-100 dark:divide-slate-700/80">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400">
                            <i class="fas fa-users-slash text-3xl mb-2 block"></i>
                            Tidak ada data pengguna terdaftar.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition">
                        <td class="py-3.5 px-4 flex items-center gap-3">
                            <img src="<?php echo htmlspecialchars($u['avatar'] ?: 'https://via.placeholder.com/40'); ?>" class="w-9 h-9 rounded-full object-cover border border-slate-200 dark:border-slate-700 shrink-0">
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white text-sm"><?php echo htmlspecialchars($u['name']); ?></div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5"><?php echo htmlspecialchars($u['department'] ?: 'Divisi belum diatur'); ?></div>
                                <?php if ($u['id'] === $_SESSION['user_id']): ?>
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 bg-brand-100 dark:bg-brand-900/60 text-brand-700 dark:text-brand-300 rounded">Akun Anda</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap font-medium text-slate-700 dark:text-slate-300">
                            <?php echo htmlspecialchars($u['email']); ?>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <?php if ($u['role'] === 'super_admin'): ?>
                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-violet-100 dark:bg-violet-950/60 text-violet-800 dark:text-violet-300 border border-violet-200 dark:border-violet-800 uppercase">SUPER ADMIN</span>
                            <?php elseif (is_super_admin() && (int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                <form method="POST" action="admin_users.php" class="inline-block">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <select name="role" onchange="this.form.submit()" class="px-2.5 py-1 text-[10px] font-bold rounded-lg border cursor-pointer focus:outline-none transition shadow-sm <?php echo $u['role'] === 'admin' ? 'bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-950/80 dark:text-amber-300 dark:border-amber-800' : 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600'; ?>">
                                        <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>USER</option>
                                        <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>ADMIN</option>
                                    </select>
                                </form>
                            <?php elseif ($u['role'] === 'admin'): ?>
                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 uppercase">ADMIN</span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600 uppercase">USER</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap text-slate-400">
                            <?php echo format_date($u['created_at']); ?>
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <?php if (is_super_admin()): ?>
                                <button type="button" onclick="openEditUserModal(<?php echo (int)$u['id']; ?>)" class="p-1.5 px-2 text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-950/40 rounded-lg transition" title="Edit Akun">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>
                            <?php endif; ?>
                            <?php if (is_super_admin() && (int)$u['id'] !== (int)$_SESSION['user_id'] && $u['role'] !== 'super_admin'): ?>
                                <form method="POST" action="admin_users.php" class="inline-block" onsubmit="return confirm('Hapus pengguna ini beserta data terkait?')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="p-1.5 px-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition" title="Hapus Pengguna">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            <?php elseif (!is_super_admin()): ?>
                                <span class="text-slate-400 text-[11px] italic">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (is_super_admin()): ?>
<div id="editUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="editUserModalTitle">
    <div class="relative w-full max-w-xl rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-800">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-950/60 dark:text-brand-300"><i class="fas fa-user-pen"></i></span>
                <div>
                    <h2 id="editUserModalTitle" class="text-lg font-bold text-slate-900 dark:text-white">Edit Akun</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Perbarui identitas, divisi, role, atau password pengguna.</p>
                </div>
            </div>
            <button type="button" onclick="closeEditUserModal()" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-700 dark:hover:text-white" aria-label="Tutup modal"><i class="fas fa-times"></i></button>
        </div>

        <form method="POST" action="admin_users.php" class="space-y-4 p-5" id="editUserForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="editUserId">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="editUserName" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Nama Lengkap</label>
                    <input type="text" name="name" id="editUserName" maxlength="100" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label for="editUserEmail" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Email</label>
                    <input type="email" name="email" id="editUserEmail" maxlength="100" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                </div>
            </div>

            <div>
                <label for="editUserDepartment" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Divisi</label>
                <select name="department" id="editUserDepartment" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    <option value="">Pilih divisi</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo htmlspecialchars($department, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($department); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="editUserRole" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Role</label>
                    <select name="role" id="editUserRole" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                        <option value="user">USER</option>
                        <option value="admin">ADMIN</option>
                        <option value="super_admin" disabled>SUPER ADMIN</option>
                    </select>
                    <p id="editUserRoleHint" class="mt-1.5 text-[10px] text-slate-500 dark:text-slate-400"></p>
                </div>
                <div>
                    <label for="editUserPassword" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Password Baru <span class="font-normal text-slate-400">(opsional)</span></label>
                    <div class="relative">
                    <input type="password" name="password" id="editUserPassword" minlength="8" autocomplete="new-password" aria-describedby="editPasswordRequirements" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white" placeholder="Kosongkan jika tidak diubah">
                        <div id="editPasswordPopover" class="pointer-events-none absolute left-0 right-0 top-full z-30 mt-2 hidden rounded-xl border border-slate-200 bg-white p-3 shadow-xl dark:border-slate-600 dark:bg-slate-900" role="status" aria-live="polite">
                            <div class="absolute -top-1.5 left-5 h-3 w-3 rotate-45 border-l border-t border-slate-200 bg-white dark:border-slate-600 dark:bg-slate-900"></div>
                            <p id="editPasswordRequirementSummary" class="relative mb-2 text-[11px] font-bold text-rose-600 dark:text-rose-400">Password belum memenuhi kriteria:</p>
                            <ul id="editPasswordRequirements" class="relative grid grid-cols-1 gap-1 text-[10px] sm:grid-cols-2">
                                <li data-password-rule="length" class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400"><i class="fas fa-circle text-[7px]"></i>Minimal 8 karakter</li>
                                <li data-password-rule="uppercase" class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400"><i class="fas fa-circle text-[7px]"></i>Huruf besar</li>
                                <li data-password-rule="lowercase" class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400"><i class="fas fa-circle text-[7px]"></i>Huruf kecil</li>
                                <li data-password-rule="number" class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400"><i class="fas fa-circle text-[7px]"></i>Angka</li>
                                <li data-password-rule="symbol" class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400"><i class="fas fa-circle text-[7px]"></i>Simbol unik</li>
                            </ul>
                        </div>
                    </div>
                    <p class="mt-1.5 text-[10px] text-slate-500 dark:text-slate-400">Minimal 8 karakter: huruf besar, kecil, angka, dan simbol.</p>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-700">
                <button type="button" onclick="closeEditUserModal()" class="rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600">Batal</button>
                <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-brand-500/20 hover:bg-brand-700"><i class="fas fa-save mr-1.5"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    const currentSessionUserId = <?php echo (int)$_SESSION['user_id']; ?>;
    const currentSessionIsSuperAdmin = <?php echo is_super_admin() ? 'true' : 'false'; ?>;
    const csrfHiddenField = '<?php echo addslashes(csrf_field()); ?>';
    const rawUsersList = <?php echo json_encode(array_map(function($u) {
        return [
            'id' => (int)$u['id'],
            'name' => $u['name'],
            'email' => $u['email'],
            'department' => $u['department'] ?? '',
            'role' => $u['role'],
            'avatar' => $u['avatar'] ?: 'https://via.placeholder.com/40',
            'created_at' => format_date($u['created_at'])
        ];
    }, $users), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

    const userSearchInput = document.getElementById('userSearchInput');
    const clearUserSearchBtn = document.getElementById('clearUserSearchBtn');
    const userSuggestionsBox = document.getElementById('userSuggestionsBox');
    const userRoleFilter = document.getElementById('userRoleFilter');
    const userDataCount = document.getElementById('userDataCount');
    const usersTableBody = document.getElementById('usersTableBody');
    const editUserModal = document.getElementById('editUserModal');
    const editUserId = document.getElementById('editUserId');
    const editUserName = document.getElementById('editUserName');
    const editUserEmail = document.getElementById('editUserEmail');
    const editUserDepartment = document.getElementById('editUserDepartment');
    const editUserRole = document.getElementById('editUserRole');
    const editUserPassword = document.getElementById('editUserPassword');
    const editUserRoleHint = document.getElementById('editUserRoleHint');
    const editUserForm = document.getElementById('editUserForm');
    const editPasswordPopover = document.getElementById('editPasswordPopover');
    const editPasswordRequirementSummary = document.getElementById('editPasswordRequirementSummary');

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    function validateEditPassword(showPopover = false) {
        if (!editUserPassword) return true;
        const value = editUserPassword.value;
        const checks = {
            length: value.length >= 8,
            uppercase: /[A-Z]/.test(value),
            lowercase: /[a-z]/.test(value),
            number: /\d/.test(value),
            symbol: /[^A-Za-z0-9]/.test(value)
        };
        const isEmpty = value.length === 0;
        const isValid = isEmpty || Object.values(checks).every(Boolean);

        Object.entries(checks).forEach(([rule, passed]) => {
            const item = document.querySelector(`[data-password-rule="${rule}"]`);
            if (!item) return;
            const icon = item.querySelector('i');
            item.classList.toggle('text-emerald-600', passed);
            item.classList.toggle('dark:text-emerald-400', passed);
            item.classList.toggle('text-rose-600', !isEmpty && !passed);
            item.classList.toggle('dark:text-rose-400', !isEmpty && !passed);
            item.classList.toggle('text-slate-500', isEmpty);
            item.classList.toggle('dark:text-slate-400', isEmpty);
            if (icon) icon.className = `fas ${passed ? 'fa-circle-check' : (!isEmpty ? 'fa-circle-xmark' : 'fa-circle')} text-[10px]`;
        });

        editUserPassword.setCustomValidity(isValid ? '' : 'Password belum memenuhi seluruh kriteria keamanan.');
        editUserPassword.setAttribute('aria-invalid', isValid ? 'false' : 'true');
        if (editPasswordRequirementSummary) {
            editPasswordRequirementSummary.textContent = isValid && !isEmpty
                ? 'Password sudah memenuhi seluruh kriteria.'
                : (isEmpty ? 'Password tidak diubah jika kolom dikosongkan.' : 'Password belum memenuhi kriteria:');
            editPasswordRequirementSummary.classList.toggle('text-emerald-600', isValid && !isEmpty);
            editPasswordRequirementSummary.classList.toggle('dark:text-emerald-400', isValid && !isEmpty);
            editPasswordRequirementSummary.classList.toggle('text-rose-600', !isValid);
            editPasswordRequirementSummary.classList.toggle('dark:text-rose-400', !isValid);
            editPasswordRequirementSummary.classList.toggle('text-slate-600', isEmpty);
            editPasswordRequirementSummary.classList.toggle('dark:text-slate-300', isEmpty);
        }
        if (editPasswordPopover && showPopover) editPasswordPopover.classList.remove('hidden');
        return isValid;
    }

    function openEditUserModal(userId) {
        if (!currentSessionIsSuperAdmin || !editUserModal) return;
        const user = rawUsersList.find(item => item.id === Number(userId));
        if (!user) return;

        editUserId.value = user.id;
        editUserName.value = user.name || '';
        editUserEmail.value = user.email || '';
        editUserDepartment.value = user.department || '';
        editUserRole.value = user.role;
        editUserRole.disabled = user.role === 'super_admin';
        editUserRoleHint.textContent = user.role === 'super_admin'
            ? 'Role Super Admin dilindungi dan tidak dapat diturunkan.'
            : 'Perubahan role berlaku pada request berikutnya.';
        editUserPassword.value = '';
        validateEditPassword(false);
        if (editPasswordPopover) editPasswordPopover.classList.add('hidden');

        editUserModal.classList.remove('hidden');
        editUserModal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        window.setTimeout(() => editUserName.focus(), 0);
    }

    function closeEditUserModal() {
        if (!editUserModal) return;
        editUserModal.classList.add('hidden');
        editUserModal.classList.remove('flex');
        if (editPasswordPopover) editPasswordPopover.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function highlightText(text, query) {
        if (!query || !text) return escapeHtml(text);
        const safeText = escapeHtml(text);
        const safeQuery = escapeHtml(query).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${safeQuery})`, 'gi');
        return safeText.replace(regex, `<mark class="bg-amber-200 dark:bg-amber-800 text-slate-900 dark:text-white rounded px-0.5 font-bold">$1</mark>`);
    }

    function clearUserSearch() {
        userSearchInput.value = '';
        clearUserSearchBtn.classList.add('hidden');
        userSuggestionsBox.classList.add('hidden');
        applyLiveUserFilter();
        userSearchInput.focus();
    }

    function selectUserSuggestion(value) {
        userSearchInput.value = value;
        userSuggestionsBox.classList.add('hidden');
        clearUserSearchBtn.classList.remove('hidden');
        applyLiveUserFilter();
    }

    function updateUserSuggestions(query) {
        if (!query || query.length < 1) {
            userSuggestionsBox.innerHTML = '';
            userSuggestionsBox.classList.add('hidden');
            return;
        }

        const q = query.toLowerCase();
        const suggestions = [];
        const seen = new Set();

        rawUsersList.forEach(u => {
            if (u.name && u.name.toLowerCase().includes(q) && !seen.has('name:' + u.name)) {
                seen.add('name:' + u.name);
                suggestions.push({ type: 'Nama', text: u.name, icon: 'fa-user text-amber-500' });
            }
            if (u.email && u.email.toLowerCase().includes(q) && !seen.has('email:' + u.email)) {
                seen.add('email:' + u.email);
                suggestions.push({ type: 'Email', text: u.email, icon: 'fa-envelope text-blue-500' });
            }
            if (u.department && u.department.toLowerCase().includes(q) && !seen.has('department:' + u.department)) {
                seen.add('department:' + u.department);
                suggestions.push({ type: 'Divisi', text: u.department, icon: 'fa-building text-brand-500' });
            }
        });

        if (suggestions.length === 0) {
            userSuggestionsBox.innerHTML = `
                <div class="px-3.5 py-2 text-[11px] text-slate-400 text-center">
                    Tidak ditemukan saran untuk "<strong>${escapeHtml(query)}</strong>"
                </div>
            `;
            userSuggestionsBox.classList.remove('hidden');
            return;
        }

        const topSuggestions = suggestions.slice(0, 6);
        let html = `
            <div class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-700/80 mb-1 flex items-center justify-between">
                <span>Rekomendasi Pengguna</span>
                <span class="text-[9px] font-normal text-slate-400">Klik untuk memilih</span>
            </div>
        `;

        topSuggestions.forEach(item => {
            const escapedVal = escapeHtml(item.text);
            const highlightedVal = highlightText(item.text, query);
            html += `
                <button 
                    type="button" 
                    onmousedown="selectUserSuggestion('${escapedVal.replace(/'/g, "\\'")}')" 
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

        userSuggestionsBox.innerHTML = html;
        userSuggestionsBox.classList.remove('hidden');
    }

    function applyLiveUserFilter() {
        const query = userSearchInput.value.trim();
        const q = query.toLowerCase();
        const selectedRole = userRoleFilter.value;

        if (query) {
            clearUserSearchBtn.classList.remove('hidden');
        } else {
            clearUserSearchBtn.classList.add('hidden');
        }

        let filtered = rawUsersList.filter(u => {
            if (selectedRole && u.role !== selectedRole) return false;
            if (!q) return true;

            const name = (u.name || '').toLowerCase();
            const email = (u.email || '').toLowerCase();
            const department = (u.department || '').toLowerCase();

            return name.includes(q) || email.includes(q) || department.includes(q);
        });

        // Priority Re-ranking: Direct name matches float to top
        if (q) {
            filtered.sort((a, b) => {
                const aName = (a.name || '').toLowerCase();
                const bName = (b.name || '').toLowerCase();

                let aScore = 0;
                let bScore = 0;

                if (aName.startsWith(q)) aScore += 100;
                else if (aName.includes(q)) aScore += 75;
                if ((a.email || '').toLowerCase().includes(q)) aScore += 40;

                if (bName.startsWith(q)) bScore += 100;
                else if (bName.includes(q)) bScore += 75;
                if ((b.email || '').toLowerCase().includes(q)) bScore += 40;

                if (bScore !== aScore) return bScore - aScore;
                return a.id - b.id;
            });
        }

        if (userDataCount) {
            userDataCount.textContent = filtered.length;
        }

        renderUsersTable(filtered, query);
    }

    function renderUsersTable(users, highlightQuery = '') {
        if (!users || users.length === 0) {
            usersTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="py-12 text-center text-slate-400">
                        <i class="fas fa-users-slash text-3xl mb-2 block"></i>
                        Tidak ada data pengguna yang sesuai dengan pencarian.
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        users.forEach(u => {
            const displayName = highlightText(u.name, highlightQuery);
            const displayEmail = highlightText(u.email, highlightQuery);
            const displayDepartment = highlightText(u.department || 'Divisi belum diatur', highlightQuery);
            const isSelf = (u.id === currentSessionUserId);

            let roleHtml = '';
            if (u.role === 'super_admin') {
                roleHtml = `
                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-violet-100 dark:bg-violet-950/60 text-violet-800 dark:text-violet-300 border border-violet-200 dark:border-violet-800 uppercase">
                        SUPER ADMIN
                    </span>
                `;
            } else if (currentSessionIsSuperAdmin && !isSelf) {
                roleHtml = `
                    <form method="POST" action="admin_users.php" class="inline-block">
                        ${csrfHiddenField}
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="user_id" value="${u.id}">
                        <select name="role" onchange="this.form.submit()" class="px-2.5 py-1 text-[10px] font-bold rounded-lg border cursor-pointer focus:outline-none transition shadow-sm ${u.role === 'admin' ? 'bg-amber-100 text-amber-900 border-amber-300 dark:bg-amber-950/80 dark:text-amber-300 dark:border-amber-800' : 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600'}">
                            <option value="user" ${u.role === 'user' ? 'selected' : ''}>USER</option>
                            <option value="admin" ${u.role === 'admin' ? 'selected' : ''}>ADMIN</option>
                        </select>
                    </form>
                `;
            } else if (u.role === 'admin') {
                roleHtml = `<span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 uppercase">ADMIN</span>`;
            } else {
                roleHtml = `<span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600 uppercase">USER</span>`;
            }

            let actionHtml = '<span class="text-slate-400 text-[11px] italic">-</span>';
            if (currentSessionIsSuperAdmin) {
                actionHtml = `
                    <button type="button" onclick="openEditUserModal(${u.id})" class="p-1.5 px-2 text-brand-600 dark:text-brand-400 hover:bg-brand-50 dark:hover:bg-brand-950/40 rounded-lg transition" title="Edit Akun">
                        <i class="fas fa-pen-to-square"></i>
                    </button>
                `;
            }
            if (currentSessionIsSuperAdmin && !isSelf && u.role !== 'super_admin') {
                actionHtml += `
                    <form method="POST" action="admin_users.php" class="inline-block" onsubmit="return confirm('Hapus pengguna ini beserta data terkait?')">
                        ${csrfHiddenField}
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="${u.id}">
                        <button type="submit" class="p-1.5 px-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition" title="Hapus Pengguna">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                `;
            }

            html += `
                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition">
                    <td class="py-3.5 px-4 flex items-center gap-3">
                        <img src="${escapeHtml(u.avatar)}" class="w-9 h-9 rounded-full object-cover border border-slate-200 dark:border-slate-700 shrink-0">
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white text-sm">${displayName}</div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">${displayDepartment}</div>
                            ${isSelf ? '<span class="text-[9px] font-bold px-1.5 py-0.5 bg-brand-100 dark:bg-brand-900/60 text-brand-700 dark:text-brand-300 rounded">Akun Anda</span>' : ''}
                        </div>
                    </td>
                    <td class="py-3.5 px-4 whitespace-nowrap font-medium text-slate-700 dark:text-slate-300">
                        ${displayEmail}
                    </td>
                    <td class="py-3.5 px-4 whitespace-nowrap">
                        ${roleHtml}
                    </td>
                    <td class="py-3.5 px-4 whitespace-nowrap text-slate-400">
                        ${escapeHtml(u.created_at)}
                    </td>
                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                        ${actionHtml}
                    </td>
                </tr>
            `;
        });

        usersTableBody.innerHTML = html;
    }

    document.addEventListener('DOMContentLoaded', () => {
        userSearchInput.addEventListener('input', () => {
            const q = userSearchInput.value.trim();
            updateUserSuggestions(q);
            applyLiveUserFilter();
        });

        userSearchInput.addEventListener('focus', () => {
            const q = userSearchInput.value.trim();
            if (q) updateUserSuggestions(q);
        });

        userRoleFilter.addEventListener('change', applyLiveUserFilter);

        if (editUserPassword) {
            editUserPassword.addEventListener('focus', () => validateEditPassword(true));
            editUserPassword.addEventListener('input', () => validateEditPassword(true));
            editUserPassword.addEventListener('blur', () => {
                if (validateEditPassword(false) && editPasswordPopover) {
                    window.setTimeout(() => editPasswordPopover.classList.add('hidden'), 120);
                }
            });
        }
        if (editUserForm) {
            editUserForm.addEventListener('submit', event => {
                if (!validateEditPassword(true)) {
                    event.preventDefault();
                    editUserPassword.focus();
                }
            });
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#userSearchContainer')) {
                userSuggestionsBox.classList.add('hidden');
            }
            if (editUserModal && e.target === editUserModal) {
                closeEditUserModal();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && editUserModal && !editUserModal.classList.contains('hidden')) {
                closeEditUserModal();
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
