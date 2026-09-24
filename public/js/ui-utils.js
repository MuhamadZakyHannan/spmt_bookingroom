(function exposeUiUtilities(global) {
    const htmlEntities = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };

    /** Meloloskan karakter HTML sebelum teks ditampilkan. */
    function escapeHtml(value) {
        if (!value) return '';
        return String(value).replace(/[&<>"']/g, character => htmlEntities[character]);
    }

    /** Menandai bagian teks yang cocok dengan kata pencarian. */
    function highlightText(value, query, className = 'bg-amber-200 dark:bg-amber-800 text-slate-900 dark:text-white rounded px-0.5 font-bold') {
        if (!query || !value) return escapeHtml(value);
        const escapedValue = escapeHtml(value);
        const escapedQuery = escapeHtml(query).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return escapedValue.replace(
            new RegExp(`(${escapedQuery})`, 'gi'),
            `<mark class="${className}">$1</mark>`
        );
    }

    /** Menghasilkan label teks status booking terpusat di frontend. */
    function getStatusLabel(booking) {
        if (!booking) return '';
        const status = booking.status || '';
        const reason = booking.status_reason || '';
        if (status === 'cancelled' && reason === 'expired') return 'Kedaluwarsa';
        if (status === 'confirmed' && reason === 'relocated_by_admin') return 'Disetujui (Dialihkan)';
        if (status === 'cancelled' && reason === 'cancelled_by_admin') return 'Dibatalkan oleh Admin';
        if (status === 'cancelled' && reason === 'conflict_not_selected') return 'Ditolak (Jadwal Bentrok)';
        switch (status) {
            case 'pending': return 'Menunggu Persetujuan';
            case 'confirmed': return 'Disetujui';
            case 'completed': return 'Selesai';
            case 'cancelled': return 'Dibatalkan / Ditolak';
            default: return 'Tidak Diketahui';
        }
    }

    /** Menghasilkan markup badge status booking terpusat di frontend. */
    function renderStatusBadge(booking) {
        if (!booking) return '';
        const status = booking.status || '';
        const reason = booking.status_reason || '';
        const notes = booking.admin_notes || '';
        const safeNotes = escapeHtml(notes);
        const titleAttr = safeNotes ? ` title="${safeNotes}"` : '';

        if (status === 'cancelled' && reason === 'expired') {
            return `
                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-700 dark:text-slate-200 dark:border-slate-600">
                    <i class="fas fa-clock-rotate-left text-slate-500"></i> Kedaluwarsa
                </span>
            `;
        }
        if (status === 'confirmed' && reason === 'relocated_by_admin') {
            return `
                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-amber-50 text-amber-900 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800"${titleAttr}>
                    <i class="fas fa-arrows-split-up-and-left text-amber-600"></i> Dialihkan
                </span>
            `;
        }
        if (status === 'confirmed') {
            return `
                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                    <i class="fas fa-check-circle text-emerald-600"></i> Disetujui
                </span>
            `;
        }
        if (status === 'completed') {
            return `
                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">
                    <i class="fas fa-check-double text-blue-600"></i> Selesai
                </span>
            `;
        }
        if (status === 'pending') {
            return `
                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-amber-50 text-amber-900 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-700 animate-pulse shadow-sm">
                    <i class="fas fa-hourglass-half text-amber-600"></i> Menunggu
                </span>
            `;
        }
        if (status === 'cancelled' && reason === 'cancelled_by_admin') {
            return `
                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800"${titleAttr}>
                    <i class="fas fa-ban text-rose-600"></i> Dibatalkan Admin
                </span>
            `;
        }
        if (status === 'cancelled' && reason === 'conflict_not_selected') {
            return `
                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-amber-50 text-amber-900 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800"${titleAttr}>
                    <i class="fas fa-calendar-xmark text-amber-600"></i> Ditolak (Jadwal Bentrok)
                </span>
            `;
        }
        return `
            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold rounded-lg border bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">
                <i class="fas fa-times-circle text-rose-600"></i> Dibatalkan
            </span>
        `;
    }

    const core = Object.freeze({ escapeHtml, highlightText });
    global.MeetSpaceUI = Object.freeze({
        escapeHtml: core.escapeHtml,
        highlightText: core.highlightText,
        getStatusLabel,
        renderStatusBadge,
    });
})(window);
