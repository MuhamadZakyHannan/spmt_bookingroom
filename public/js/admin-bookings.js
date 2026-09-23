/**
 * Modul JavaScript Kelola Pemesanan Admin
 * Menangani Live Search, Autocomplete, Sinkronisasi Real-Time, Tab Switcher, dan Modal Aksi.
 */
(() => {
    'use strict';

    const config = window.AdminBookingsConfig || {};
    const csrfHiddenField = config.csrfField || '';
    let rawBookingsList = Array.isArray(config.initialBookings) ? config.initialBookings : [];
    let currentHash = '';
    let previousPendingCount = parseInt(config.initialPendingCount || 0, 10);

    const { escapeHtml, highlightText } = window.MeetSpaceUI || {
        escapeHtml: (str) => String(str).replace(/[&<>'"]/g, tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)),
        highlightText: (text, query) => text
    };

    /**
     * Menerapkan debounce pada pemanggilan fungsi untuk efisiensi eksekusi saat input beruntun.
     * @param {Function} func Fungsi target yang akan dieksekusi setelah jeda waktu.
     * @param {number} wait Durasi penundaan dalam milidetik.
     * @returns {Function} Fungsi hasil bungkus debounce.
     */
    function debounce(func, wait = 200) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    /**
     * Berpindah antar tab tampilan (Konflik SAW vs Semua Pemesanan).
     * @param {string} tab Nama tab tujuan ('conflicts' atau 'all').
     * @returns {void}
     */
    function switchBookingTab(tab) {
        const tabConflicts = document.getElementById('tabContentConflicts');
        const tabAll = document.getElementById('tabContentAll');
        const btnConflicts = document.getElementById('tabBtnConflicts');
        const btnAll = document.getElementById('tabBtnAll');
        
        if (!tabConflicts || !tabAll || !btnConflicts || !btnAll) return;

        if (tab === 'conflicts') {
            tabConflicts.classList.remove('hidden');
            tabAll.classList.add('hidden');
            btnConflicts.classList.add('border-amber-500', 'text-amber-600', 'dark:text-amber-400', 'bg-amber-50/60', 'dark:bg-amber-950/30', 'rounded-t-xl');
            btnConflicts.classList.remove('border-transparent', 'text-slate-500', 'dark:text-slate-400');
            btnAll.classList.remove('border-amber-500', 'text-amber-600', 'dark:text-amber-400', 'bg-amber-50/60', 'dark:bg-amber-950/30', 'rounded-t-xl');
            btnAll.classList.add('border-transparent', 'text-slate-500', 'dark:text-slate-400');
            try { history.replaceState(null, '', '#conflicts'); } catch(e) {}
        } else {
            tabAll.classList.remove('hidden');
            tabConflicts.classList.add('hidden');
            btnAll.classList.add('border-amber-500', 'text-amber-600', 'dark:text-amber-400', 'bg-amber-50/60', 'dark:bg-amber-950/30', 'rounded-t-xl');
            btnAll.classList.remove('border-transparent', 'text-slate-500', 'dark:text-slate-400');
            btnConflicts.classList.remove('border-amber-500', 'text-amber-600', 'dark:text-amber-400', 'bg-amber-50/60', 'dark:bg-amber-950/30', 'rounded-t-xl');
            btnConflicts.classList.add('border-transparent', 'text-slate-500', 'dark:text-slate-400');
            try { history.replaceState(null, '', '#all'); } catch(e) {}
        }
    }

    /**
     * Membuka atau menutup accordion panduan kriteria bobot SPK SAW.
     * @returns {void}
     */
    function toggleSawGuide() {
        const guide = document.getElementById('sawGuideContent');
        const btnText = document.getElementById('sawGuideBtnText');
        if (!guide) return;
        if (guide.classList.contains('hidden')) {
            guide.classList.remove('hidden');
            btnText.textContent = 'Sembunyikan Kriteria';
        } else {
            guide.classList.add('hidden');
            btnText.textContent = 'Lihat Kriteria & Bobot';
        }
    }

    /**
     * Membuka modal rincian agenda pemesanan.
     * @param {Object} data Objek informasi agenda rapat.
     * @returns {void}
     */
    function openDetailModal(data) {
        document.getElementById('modalTitle').textContent = data.title;
        document.getElementById('modalPurpose').textContent = data.purpose;
        document.getElementById('modalUser').textContent = data.userName;
        document.getElementById('modalDept').textContent = data.userDept + ' (' + data.userEmail + ')';
        document.getElementById('modalRoom').textContent = data.roomName + (data.roomCode ? ' [' + data.roomCode + ']' : '');
        document.getElementById('modalAttendees').textContent = 'Kapasitas: ' + data.attendees;
        document.getElementById('modalSchedule').textContent = data.date + ' • ' + data.time;
        
        const notesContainer = document.getElementById('modalAdminNotesContainer');
        const notesLabel = document.getElementById('modalAdminNotesLabel');
        const notesEl = document.getElementById('modalAdminNotes');
        if (notesContainer && notesEl) {
            if (data.adminNotes) {
                notesEl.textContent = data.adminNotes;
                if (data.status === 'cancelled') {
                    notesLabel.textContent = 'Alasan Pembatalan dari Admin:';
                    notesContainer.className = 'p-2.5 bg-rose-50 dark:bg-rose-950/40 rounded-xl border border-rose-200 dark:border-rose-900/60';
                    notesLabel.className = 'text-[10px] text-rose-700 dark:text-rose-400 block font-bold';
                    notesEl.className = 'text-xs text-rose-900 dark:text-rose-200 block font-medium mt-0.5 whitespace-pre-wrap';
                } else {
                    notesLabel.textContent = 'Keterangan Pengalihan dari Admin:';
                    notesContainer.className = 'p-2.5 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-900/60';
                    notesLabel.className = 'text-[10px] text-amber-700 dark:text-amber-400 block font-bold';
                    notesEl.className = 'text-xs text-amber-900 dark:text-amber-200 block font-medium mt-0.5 whitespace-pre-wrap';
                }
                notesContainer.classList.remove('hidden');
            } else {
                notesContainer.classList.add('hidden');
            }
        }

        const modal = document.getElementById('detailModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    /**
     * Menutup modal rincian agenda pemesanan.
     * @returns {void}
     */
    function closeDetailModal() {
        const modal = document.getElementById('detailModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    /**
     * Membuka modal konfirmasi pembatalan pemesanan dengan form input alasan.
     * @param {Object} data Objek data booking yang akan dibatalkan.
     * @returns {void}
     */
    function openCancelModal(data) {
        document.getElementById('cancelModalBookingId').value = data.id;
        document.getElementById('cancelModalTitle').textContent = data.title;
        document.getElementById('cancelModalInfo').textContent = (data.userName || '') + ' • ' + (data.roomName || '') + ' (' + (data.date || '') + ' ' + (data.time || '') + ')';
        document.getElementById('cancelModalReason').value = '';
        const modal = document.getElementById('cancelBookingModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        window.setTimeout(() => document.getElementById('cancelModalReason').focus(), 50);
    }

    /**
     * Menutup modal konfirmasi pembatalan pemesanan.
     * @returns {void}
     */
    function closeCancelModal() {
        const modal = document.getElementById('cancelBookingModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    /**
     * Membuka modal pengalihan ruangan dengan dropdown ruangan pengganti dan input catatan.
     * @param {Object} data Objek data booking yang akan dialihkan.
     * @returns {void}
     */
    function openRelocateModal(data) {
        document.getElementById('relocateModalBookingId').value = data.id;
        document.getElementById('relocateModalTitle').textContent = data.title;
        document.getElementById('relocateModalInfo').textContent = (data.userName || '') + ' • Ruangan Saat Ini: ' + (data.roomName || '') + ' (' + (data.date || '') + ' ' + (data.time || '') + ')';
        document.getElementById('relocateModalReason').value = '';

        const select = document.getElementById('relocateNewRoomSelect');
        if (select) {
            Array.from(select.options).forEach(opt => {
                opt.disabled = (parseInt(opt.value, 10) === parseInt(data.roomId, 10));
            });
            select.selectedIndex = 0;
            if (select.selectedOptions[0]?.disabled && select.options[1]) {
                select.selectedIndex = 1;
            }
        }

        const modal = document.getElementById('relocateBookingModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        window.setTimeout(() => document.getElementById('relocateModalReason').focus(), 50);
    }

    /**
     * Menutup modal pengalihan ruangan.
     * @returns {void}
     */
    function closeRelocateModal() {
        const modal = document.getElementById('relocateBookingModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    /**
     * Menampilkan notifikasi toast real-time di sudut layar.
     * @param {string} message Pesan yang akan ditampilkan.
     * @param {string} [type='info'] Tipe toast ('info' atau 'success').
     * @returns {void}
     */
    function showToast(message, type = 'info') {
        const container = document.getElementById('liveToastContainer');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = 'pointer-events-auto flex items-center gap-3 px-4 py-3 bg-slate-900/95 text-white dark:bg-white dark:text-slate-900 rounded-2xl shadow-2xl border border-slate-700 text-xs font-semibold transform translate-y-2 opacity-0 transition-all duration-300';
        
        let iconHtml = '<i class="fas fa-bell text-amber-400"></i>';
        if (type === 'success') iconHtml = '<i class="fas fa-check-circle text-emerald-400"></i>';

        toast.innerHTML = `
            ${iconHtml}
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" class="ml-2 text-slate-400 hover:text-white p-1">
                <i class="fas fa-times text-xs"></i>
            </button>
        `;
        
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.remove('translate-y-2', 'opacity-0');
        }, 10);

        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    /**
     * Menghapus dan mereset input pencarian langsung.
     * @returns {void}
     */
    function clearSearchInput() {
        const searchInput = document.getElementById('searchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const suggestionsBox = document.getElementById('searchSuggestionsBox');
        if (!searchInput) return;
        searchInput.value = '';
        if (clearSearchBtn) clearSearchBtn.classList.add('hidden');
        if (suggestionsBox) suggestionsBox.classList.add('hidden');
        applyLiveFilter();
        searchInput.focus();
    }

    /**
     * Memilih salah satu saran rekomendasi dari autokomplit pencarian.
     * @param {string} value Nilai rekomendasi yang dipilih.
     * @returns {void}
     */
    function selectSuggestion(value) {
        const searchInput = document.getElementById('searchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const suggestionsBox = document.getElementById('searchSuggestionsBox');
        if (!searchInput) return;
        searchInput.value = value;
        if (suggestionsBox) suggestionsBox.classList.add('hidden');
        if (clearSearchBtn) clearSearchBtn.classList.remove('hidden');
        applyLiveFilter();
    }

    /**
     * Memperbarui daftar saran rekomendasi autokomplit pencarian berdasarkan input pengguna.
     * @param {string} query Kata kunci pencarian.
     * @returns {void}
     */
    function updateAutocompleteSuggestions(query) {
        const suggestionsBox = document.getElementById('searchSuggestionsBox');
        if (!suggestionsBox) return;

        if (!query || query.length < 1) {
            suggestionsBox.innerHTML = '';
            suggestionsBox.classList.add('hidden');
            return;
        }

        const q = query.toLowerCase();
        const suggestions = [];
        const seen = new Set();

        rawBookingsList.forEach(b => {
            if (b.title && b.title.toLowerCase().includes(q) && !seen.has('title:' + b.title)) {
                seen.add('title:' + b.title);
                suggestions.push({ type: 'Agenda', text: b.title, icon: 'fa-calendar-alt text-amber-500' });
            }
            if (b.user_name && b.user_name.toLowerCase().includes(q) && !seen.has('user:' + b.user_name)) {
                seen.add('user:' + b.user_name);
                suggestions.push({ type: 'Pemesan', text: b.user_name, icon: 'fa-user text-blue-500' });
            }
            if (b.user_dept && b.user_dept.toLowerCase().includes(q) && !seen.has('dept:' + b.user_dept)) {
                seen.add('dept:' + b.user_dept);
                suggestions.push({ type: 'Divisi', text: b.user_dept, icon: 'fa-building text-indigo-500' });
            }
            if (b.room_name && b.room_name.toLowerCase().includes(q) && !seen.has('room:' + b.room_name)) {
                seen.add('room:' + b.room_name);
                suggestions.push({ type: 'Ruangan', text: b.room_name, icon: 'fa-door-open text-emerald-500' });
            }
        });

        if (suggestions.length === 0) {
            suggestionsBox.innerHTML = `
                <div class="px-3.5 py-2 text-[11px] text-slate-400 text-center">
                    Tidak ditemukan rekomendasi untuk "<strong>${escapeHtml(query)}</strong>"
                </div>
            `;
            suggestionsBox.classList.remove('hidden');
            return;
        }

        const topSuggestions = suggestions.slice(0, 6);
        let html = `
            <div class="px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-slate-700/80 mb-1 flex items-center justify-between">
                <span>Rekomendasi Pencarian</span>
                <span class="text-[9px] font-normal text-slate-400">Klik untuk memilih</span>
            </div>
        `;

        topSuggestions.forEach(item => {
            const escapedVal = escapeHtml(item.text);
            const highlightedVal = highlightText(item.text, query);
            html += `
                <button 
                    type="button" 
                    onmousedown="selectSuggestion('${escapedVal.replace(/'/g, "\\'")}')" 
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

        suggestionsBox.innerHTML = html;
        suggestionsBox.classList.remove('hidden');
    }

    /**
     * Menerapkan filter live pencarian dan status ke dataset serta merender ulang tabel.
     * @returns {void}
     */
    function applyLiveFilter() {
        const searchInput = document.getElementById('searchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const statusSelect = document.getElementById('statusSelect');
        if (!searchInput || !statusSelect) return;

        const query = searchInput.value.trim();
        const q = query.toLowerCase();
        const statusFilter = statusSelect.value;

        if (query) {
            clearSearchBtn?.classList.remove('hidden');
        } else {
            clearSearchBtn?.classList.add('hidden');
        }

        let filtered = rawBookingsList.filter(b => {
            if (statusFilter && b.status !== statusFilter) {
                return false;
            }
            if (!q) return true;

            const t = (b.title || '').toLowerCase();
            const p = (b.purpose || '').toLowerCase();
            const u = (b.user_name || '').toLowerCase();
            const d = (b.user_dept || '').toLowerCase();
            const r = (b.room_name || '').toLowerCase();
            const c = (b.room_code || '').toLowerCase();

            return t.includes(q) || p.includes(q) || u.includes(q) || d.includes(q) || r.includes(q) || c.includes(q);
        });

        if (q) {
            filtered.sort((a, b) => {
                const aTitle = (a.title || '').toLowerCase();
                const bTitle = (b.title || '').toLowerCase();

                let aScore = 0;
                let bScore = 0;

                if (aTitle.startsWith(q)) aScore += 100;
                else if (aTitle.includes(q)) aScore += 75;
                if ((a.purpose || '').toLowerCase().includes(q)) aScore += 40;
                if ((a.user_name || '').toLowerCase().includes(q)) aScore += 30;
                if ((a.room_name || '').toLowerCase().includes(q)) aScore += 20;

                if (bTitle.startsWith(q)) bScore += 100;
                else if (bTitle.includes(q)) bScore += 75;
                if ((b.purpose || '').toLowerCase().includes(q)) bScore += 40;
                if ((b.user_name || '').toLowerCase().includes(q)) bScore += 30;
                if ((b.room_name || '').toLowerCase().includes(q)) bScore += 20;

                if (bScore !== aScore) {
                    return bScore - aScore;
                }

                const aPending = a.status === 'pending' ? 1 : 0;
                const bPending = b.status === 'pending' ? 1 : 0;
                if (bPending !== aPending) return bPending - aPending;

                return b.id - a.id;
            });
        } else {
            filtered.sort((a, b) => {
                const aPending = a.status === 'pending' ? 1 : 0;
                const bPending = b.status === 'pending' ? 1 : 0;
                if (bPending !== aPending) return bPending - aPending;
                return b.id - a.id;
            });
        }

        renderBookingsTable(filtered, query);
    }

    /**
     * Merender tabel data booking secara dinamis pada client-side.
     * @param {Array<Object>} bookings Daftar data booking.
     * @param {string} [highlightQuery=''] Kata kunci untuk penandaan teks.
     * @returns {void}
     */
    function renderBookingsTable(bookings, highlightQuery = '') {
        const tbody = document.getElementById('bookingsTableBody');
        if (!tbody) return;

        if (!bookings || bookings.length === 0) {
            tbody.innerHTML = `
                <tr id="emptyRow">
                    <td colspan="7" class="py-12 text-center text-slate-400">
                        <i class="fas fa-inbox text-3xl mb-2 block"></i>
                        Tidak ada data booking yang sesuai dengan kriteria pencarian.
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        bookings.forEach(b => {
            const isPending = (b.status === 'pending');
            const purposeText = b.purpose || 'Tanpa catatan tambahan.';
            
            const modalData = {
                title: b.title,
                purpose: purposeText,
                userName: b.user_name,
                userDept: b.user_dept || 'Internal',
                userEmail: b.userEmail || b.user_email || '-',
                roomName: b.room_name,
                roomCode: b.room_code || '',
                date: b.formatted_date,
                time: b.start_time + ' - ' + b.end_time + ' WIB',
                attendees: b.attendees_count + ' Orang',
                status: b.status,
                status_reason: b.status_reason || null,
                adminNotes: b.admin_notes || ''
            };

            const encodedData = escapeHtml(JSON.stringify(modalData));
            const itemPayload = {
                id: b.id,
                title: b.title,
                userName: b.user_name,
                roomId: b.room_id,
                roomName: b.room_name,
                date: b.formatted_date,
                time: b.start_time + ' - ' + b.end_time + ' WIB',
            };
            const encodedItemPayload = escapeHtml(JSON.stringify(itemPayload));

            const displayTitle = highlightText(b.title, highlightQuery);
            const displayPurpose = highlightText(purposeText, highlightQuery);
            const displayUser = highlightText(b.user_name, highlightQuery);
            const displayDept = highlightText(b.user_dept || 'Divisi Terkait', highlightQuery);
            const displayRoom = highlightText(b.room_name, highlightQuery);

            const statusHtml = window.MeetSpaceUI.renderStatusBadge(b);

            let actionHtml = '';
            const editAction = (b.status === 'pending' || b.status === 'confirmed') ? `
                <a href="edit_booking.php?id=${b.id}&amp;return_to=admin_bookings.php" class="p-1.5 px-2 bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/40 dark:hover:bg-brand-900/50 text-brand-700 dark:text-brand-300 border border-brand-200 dark:border-brand-800 font-bold rounded-lg transition text-xs flex items-center gap-1" title="Edit Booking">
                    <i class="fas fa-pen-to-square"></i>
                </a>
            ` : '';
            if (isPending) {
                actionHtml = `
                    <div class="flex items-center justify-center gap-1.5">
                        ${editAction}
                        <form method="POST" action="admin_bookings.php" class="inline-block">
                            ${csrfHiddenField}
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="booking_id" value="${b.id}">
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit" class="p-1.5 px-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition text-xs shadow flex items-center gap-1" title="Setujui Rapat">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        <button type="button" onclick="openCancelModal(${encodedItemPayload})" class="p-1.5 px-2 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold rounded-lg transition text-xs flex items-center gap-1 cursor-pointer" title="Tolak / Batalkan Pengajuan (Sertakan Catatan Alasan)">
                            <i class="fas fa-times"></i>
                        </button>
                        <form method="POST" action="admin_bookings.php" class="inline-block" onsubmit="return confirm('Hapus permanen data pemesanan ini?')">
                            ${csrfHiddenField}
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="booking_id" value="${b.id}">
                            <button type="submit" class="p-1.5 px-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition" title="Hapus Permanen">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>
                        </form>
                    </div>
                `;
            } else if (b.status === 'confirmed') {
                actionHtml = `
                    <div class="flex items-center justify-center gap-1.5">
                        ${editAction}
                        <button type="button" onclick="openRelocateModal(${encodedItemPayload})" class="p-1.5 px-2 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 dark:hover:bg-amber-900/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 font-bold rounded-lg transition text-xs flex items-center gap-1 cursor-pointer" title="Alihkan / Pindahkan Ruangan">
                            <i class="fas fa-arrows-split-up-and-left"></i>
                        </button>
                        <button type="button" onclick="openCancelModal(${encodedItemPayload})" class="p-1.5 px-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/50 text-rose-600 dark:text-rose-300 border border-rose-200 dark:border-rose-800 font-bold rounded-lg transition text-xs flex items-center gap-1 cursor-pointer" title="Batalkan Pemesanan (Sertakan Catatan Alasan)">
                            <i class="fas fa-ban"></i>
                        </button>
                        <form method="POST" action="admin_bookings.php" class="inline-block" onsubmit="return confirm('Hapus permanen data pemesanan ini?')">
                            ${csrfHiddenField}
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="booking_id" value="${b.id}">
                            <button type="submit" class="p-1.5 px-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition" title="Hapus Permanen">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>
                        </form>
                    </div>
                `;
            } else {
                actionHtml = `
                    <div class="flex items-center justify-center gap-1.5">
                        <form method="POST" action="admin_bookings.php" class="inline-block" onsubmit="return confirm('Hapus permanen data pemesanan ini?')">
                            ${csrfHiddenField}
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="booking_id" value="${b.id}">
                            <button type="submit" class="p-1.5 px-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition" title="Hapus Permanen">
                                <i class="fas fa-trash-alt text-xs"></i>
                            </button>
                        </form>
                    </div>
                `;
            }

            html += `
                <tr id="booking-row-${b.id}" class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition ${isPending ? 'bg-amber-50/50 dark:bg-amber-950/25 border-l-4 border-amber-500' : ''}">
                    <!-- 1. Agenda -->
                    <td class="py-3.5 px-4 overflow-hidden">
                        <div class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm truncate" title="${escapeHtml(b.title)}">
                            ${displayTitle}
                        </div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate mt-0.5" title="${escapeHtml(purposeText)}">
                            ${displayPurpose}
                        </div>
                        <div class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                            <i class="fas fa-users text-slate-400"></i>
                            <span>Peserta: ${b.attendees_count} Orang</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-1 mt-1.5">
                            ${b.activity_type_label ? `
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200">
                                    <i class="fas fa-tag text-[9px] text-amber-500"></i> ${escapeHtml(b.activity_type_label)}
                                </span>
                            ` : ''}
                            ${b.document_id ? `
                                <a href="booking_document.php?id=${b.document_id}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-violet-50 dark:bg-violet-950/50 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 transition" title="${escapeHtml(b.document_name || '')}">
                                    <i class="fas fa-file-lines"></i> Surat Pendukung
                                </a>
                            ` : ''}
                            ${b.is_conflict ? `
                                <button type="button" onclick="switchBookingTab('conflicts')" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800 hover:bg-rose-200 transition shadow-xs cursor-pointer" title="Jadwal ini bertabrakan! Klik untuk membuka analisis SAW.">
                                    <i class="fas fa-exclamation-triangle text-rose-600 animate-pulse"></i> Bentrok Jadwal (SPK SAW)
                                </button>
                            ` : ''}
                        </div>
                    </td>

                    <!-- 2. Pemesan & Divisi -->
                    <td class="py-3.5 px-4 overflow-hidden">
                        <div class="font-bold text-slate-800 dark:text-slate-200 text-xs truncate flex items-center gap-1.5" title="${escapeHtml(b.user_name)}">
                            <i class="fas fa-user-circle text-slate-400 shrink-0"></i>
                            <span class="truncate">${displayUser}</span>
                        </div>
                        <div class="text-[11px] text-brand-600 dark:text-brand-400 font-semibold truncate mt-0.5" title="${escapeHtml(b.user_dept || 'Divisi Terkait')}">
                            ${displayDept}
                        </div>
                        <div class="text-[10px] text-slate-400 font-mono truncate mt-0.5" title="${escapeHtml(b.userEmail || b.user_email || '')}">
                            ${escapeHtml(b.userEmail || b.user_email || '')}
                        </div>
                    </td>

                    <!-- 3. Ruangan -->
                    <td class="py-3.5 px-4 align-top">
                        <div class="font-bold text-slate-800 dark:text-slate-200 text-xs leading-snug break-words" title="${escapeHtml(b.room_name)}">
                            ${displayRoom}
                        </div>
                        <div class="text-[10px] text-brand-600 dark:text-brand-400 font-mono font-semibold mt-1">
                            [${escapeHtml(b.room_code)}]
                        </div>
                    </td>

                    <!-- 4. Tanggal & Waktu -->
                    <td class="py-3.5 px-4 whitespace-nowrap overflow-hidden">
                        <div class="font-semibold text-slate-800 dark:text-slate-200 text-xs">
                            ${escapeHtml(b.formatted_date)}
                        </div>
                        <div class="text-[11px] text-slate-600 dark:text-slate-400 font-mono font-bold mt-0.5 bg-slate-100 dark:bg-slate-900 px-2 py-0.5 rounded inline-block">
                            ${escapeHtml(b.start_time)} - ${escapeHtml(b.end_time)}
                        </div>
                    </td>

                    <!-- 5. Status -->
                    <td class="py-3.5 px-4 text-center whitespace-nowrap overflow-hidden">
                        ${statusHtml}
                    </td>

                    <!-- 6. Detail -->
                    <td class="py-3.5 px-4 text-center whitespace-nowrap overflow-hidden">
                        <button 
                            type="button" 
                            onclick="openDetailModal(${encodedData})"
                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold rounded-lg transition text-xs inline-flex items-center gap-1 shadow-sm"
                            title="Lihat Detail Rapat"
                        >
                            <i class="fas fa-eye text-brand-600 dark:text-brand-400"></i> Detail
                        </button>
                    </td>

                    <!-- 7. Aksi -->
                    <td class="py-3.5 px-4 text-center whitespace-nowrap overflow-hidden">
                        ${actionHtml}
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    /**
     * Menyelaraskan daftar booking admin dengan data terbaru dari server secara real-time.
     * @returns {Promise<void>}
     */
    async function syncAdminBookings() {
        try {
            const url = `api/admin_bookings_live.php`;
            const response = await fetch(url);
            if (!response.ok) return;

            const data = await response.json();
            if (!data.success) return;

            const pendingBadge = document.getElementById('pendingBadgeCount');
            const pendingText = document.getElementById('pendingCountText');
            if (pendingBadge && pendingText) {
                if (data.pending_count > 0) {
                    pendingText.textContent = `${data.pending_count} Menunggu Approval`;
                    pendingBadge.classList.remove('hidden');
                    pendingBadge.classList.add('flex');
                } else {
                    pendingBadge.classList.add('hidden');
                    pendingBadge.classList.remove('flex');
                }
            }

            if (currentHash && data.hash !== currentHash) {
                rawBookingsList = data.bookings;
                applyLiveFilter();

                if (data.pending_count > previousPendingCount) {
                    showToast(`🔔 Ada ${data.pending_count - previousPendingCount} pengajuan pemesanan baru masuk!`, 'info');
                } else {
                    showToast(`Data pemesanan diperbarui otomatis.`, 'success');
                }
            } else if (!currentHash) {
                rawBookingsList = data.bookings;
            }

            currentHash = data.hash;
            previousPendingCount = data.pending_count;

        } catch (err) {
            console.error('Auto-sync error:', err);
        }
    }

    // Expose handlers to global window for HTML inline attributes
    window.switchBookingTab = switchBookingTab;
    window.toggleSawGuide = toggleSawGuide;
    window.openDetailModal = openDetailModal;
    window.closeDetailModal = closeDetailModal;
    window.openCancelModal = openCancelModal;
    window.closeCancelModal = closeCancelModal;
    window.openRelocateModal = openRelocateModal;
    window.closeRelocateModal = closeRelocateModal;
    window.showToast = showToast;
    window.clearSearchInput = clearSearchInput;
    window.selectSuggestion = selectSuggestion;
    window.applyLiveFilter = applyLiveFilter;

    // Initialization on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('searchInput');
        const statusSelect = document.getElementById('statusSelect');
        const suggestionsBox = document.getElementById('searchSuggestionsBox');

        if (searchInput) {
            const handleSearchInput = debounce(() => {
                const q = searchInput.value.trim();
                updateAutocompleteSuggestions(q);
                applyLiveFilter();
            }, 180);

            searchInput.addEventListener('input', handleSearchInput);

            searchInput.addEventListener('focus', () => {
                const q = searchInput.value.trim();
                if (q) updateAutocompleteSuggestions(q);
            });
        }

        if (statusSelect) {
            statusSelect.addEventListener('change', () => {
                applyLiveFilter();
            });
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#searchContainer') && suggestionsBox) {
                suggestionsBox.classList.add('hidden');
            }
        });

        if (window.location.hash === '#all') {
            switchBookingTab('all');
        } else if (window.location.hash === '#conflicts') {
            switchBookingTab('conflicts');
        }

        syncAdminBookings();
        setInterval(syncAdminBookings, 4000);
    });
})();
