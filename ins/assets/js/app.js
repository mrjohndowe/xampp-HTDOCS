(function () {
    'use strict';

    const root = document.documentElement;
    const storageKey = 'insurance-theme';

    function applyTheme(theme) {
        const dark = theme === 'dark';

        root.classList.toggle('dark-mode', dark);

        const toggle = document.getElementById('theme-toggle');

        if (toggle) {
            toggle.textContent = dark ? '☀️' : '🌙';
            toggle.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
            toggle.setAttribute('title', dark ? 'Switch to light mode' : 'Switch to dark mode');
        }
    }

    function initializeTheme() {
        const savedTheme = localStorage.getItem(storageKey);

        if (savedTheme === 'dark' || savedTheme === 'light') {
            applyTheme(savedTheme);
            return;
        }

        const prefersDark = window.matchMedia &&
            window.matchMedia('(prefers-color-scheme: dark)').matches;

        applyTheme(prefersDark ? 'dark' : 'light');
    }

    function initializeThemeToggle() {
        const toggle = document.getElementById('theme-toggle');

        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', function () {
            const dark = root.classList.contains('dark-mode');
            const newTheme = dark ? 'light' : 'dark';

            localStorage.setItem(storageKey, newTheme);
            applyTheme(newTheme);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeTheme();
        initializeThemeToggle();
    });
})();
