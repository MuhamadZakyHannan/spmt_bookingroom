<!-- Live Notification Toast Container -->
<div id="liveToastContainer" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none"></div>

<!-- Modal Detail Agenda Pertemuan -->
<div id="detailModal" class="responsive-modal fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center">
    <div class="responsive-modal-panel bg-white dark:bg-slate-800 rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl border border-slate-200 dark:border-slate-700 animate-in fade-in zoom-in-95 duration-150 space-y-4">
        <div class="flex items-start justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300">
                    Rincian Agenda Pertemuan
                </span>
                <h3 id="modalTitle" class="text-base font-bold text-slate-900 dark:text-white mt-1 break-words"></h3>
            </div>
            <button onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="space-y-3 text-xs text-slate-600 dark:text-slate-300">
            <div class="bg-slate-50 dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200/80 dark:border-slate-800">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Catatan / Deskripsi Rapat:</div>
                <div id="modalPurpose" class="text-xs text-slate-800 dark:text-slate-200 leading-relaxed break-words break-all whitespace-pre-wrap font-medium"></div>
            </div>

            <div class="responsive-modal-grid">
                <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] text-slate-400 block font-medium">Pemesan (PIC):</span>
                    <span id="modalUser" class="font-bold text-slate-800 dark:text-slate-200 block"></span>
                    <span id="modalDept" class="text-[10px] text-brand-600 dark:text-brand-400 block font-semibold"></span>
                </div>
                <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] text-slate-400 block font-medium">Ruang Rapat:</span>
                    <span id="modalRoom" class="font-bold text-slate-800 dark:text-slate-200 block"></span>
                    <span id="modalAttendees" class="text-[10px] text-slate-500 block"></span>
                </div>
            </div>

            <div class="p-2.5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 block font-medium">Waktu & Jadwal:</span>
                <span id="modalSchedule" class="font-bold text-slate-800 dark:text-slate-200 block"></span>
            </div>

            <div id="modalAdminNotesContainer" class="hidden p-2.5 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-900/60">
                <span class="text-[10px] text-amber-700 dark:text-amber-400 block font-bold" id="modalAdminNotesLabel">Catatan / Keterangan Admin:</span>
                <span id="modalAdminNotes" class="text-xs text-amber-900 dark:text-amber-200 block font-medium mt-0.5 whitespace-pre-wrap"></span>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex justify-end">
            <button onclick="closeDetailModal()" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold rounded-xl text-xs transition">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- Modal Batalkan Pemesanan oleh Admin -->
<div id="cancelBookingModal" class="responsive-modal fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center">
    <div class="responsive-modal-panel bg-white dark:bg-slate-800 rounded-2xl max-w-lg w-full p-4 sm:p-6 shadow-2xl border border-slate-200 dark:border-slate-700 animate-in fade-in zoom-in-95 duration-150 space-y-4">
        <div class="flex items-start justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                    Konfirmasi Pembatalan
                </span>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mt-1">Batalkan Pemesanan Ruang</h3>
            </div>
            <button type="button" onclick="closeCancelModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <form method="POST" action="admin_bookings.php" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="cancel_by_admin">
            <input type="hidden" name="booking_id" id="cancelModalBookingId" value="">

            <div class="p-3 bg-slate-50 dark:bg-slate-900/70 rounded-xl border border-slate-200/80 dark:border-slate-800 text-xs">
                <div class="font-bold text-slate-800 dark:text-slate-200 text-sm" id="cancelModalTitle"></div>
                <div class="text-slate-500 dark:text-slate-400 mt-1" id="cancelModalInfo"></div>
            </div>

            <div>
                <label for="cancelModalReason" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                    Catatan / Alasan Pembatalan <span class="text-rose-500">*</span>
                </label>
                <textarea 
                    name="cancel_reason" 
                    id="cancelModalReason" 
                    rows="3" 
                    required 
                    placeholder="Contoh: Ruangan mengalami kendala operasional mendadak (kerusakan AC) atau jadwal dialihkan."
                    class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-rose-500 transition resize-none"
                ></textarea>
                <p class="text-[11px] text-slate-400 mt-1">Catatan ini akan langsung tampil di akun pemohon pada halaman <em>Booking Saya</em>.</p>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex justify-end gap-2">
                <button type="button" onclick="closeCancelModal()" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold rounded-xl text-xs transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-md shadow-rose-500/20 transition flex items-center gap-1.5">
                    <i class="fas fa-ban"></i> Batalkan & Kirim Alasan
                </button>
            </div>
        </form>
    </div>
</div>

