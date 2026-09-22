(function exposeUiUtilities(global) {
    const htmlEntities = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };

    function escapeHtml(value) {
        if (!value) return '';
        return String(value).replace(/[&<>"']/g, character => htmlEntities[character]);
    }

    function highlightText(value, query, className = 'bg-amber-200 dark:bg-amber-800 text-slate-900 dark:text-white rounded px-0.5 font-bold') {
        if (!query || !value) return escapeHtml(value);
        const escapedValue = escapeHtml(value);
        const escapedQuery = escapeHtml(query).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return escapedValue.replace(
            new RegExp(`(${escapedQuery})`, 'gi'),
            `<mark class="${className}">$1</mark>`
        );
    }

    global.MeetSpaceUI = Object.freeze({ escapeHtml, highlightText });
})(window);
