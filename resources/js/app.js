const themeKey = 'helpdesk-theme';

function applyTheme(theme) {
    const isDark = theme === 'dark';
    document.documentElement.dataset.theme = isDark ? 'dark' : 'light';

    const toggle = document.getElementById('theme-toggle');
    if (toggle) {
        toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        toggle.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        const icon = toggle.querySelector('.theme-toggle-icon');
        const label = toggle.querySelector('.theme-toggle-label');
        if (icon) icon.textContent = isDark ? '☀' : '☾';
        if (label) label.textContent = isDark ? 'Light mode' : 'Dark mode';
    }
}

const savedTheme = localStorage.getItem(themeKey);
applyTheme(savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));

document.addEventListener('DOMContentLoaded', () => {
    applyTheme(document.documentElement.dataset.theme || 'light');

    document.getElementById('theme-toggle')?.addEventListener('click', () => {
        const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        localStorage.setItem(themeKey, nextTheme);
        applyTheme(nextTheme);
    });
});
