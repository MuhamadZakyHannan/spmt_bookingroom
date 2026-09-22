(function initializeTheme() {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const dark = localStorage.theme === 'dark'
        || (!Object.prototype.hasOwnProperty.call(localStorage, 'theme') && prefersDark);
    document.documentElement.classList.toggle('dark', dark);
})();
