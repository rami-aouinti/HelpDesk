const themeKey = 'helpdesk-theme';
const accentKey = 'helpdesk-accent';
const availableAccents = ['indigo', 'blue', 'emerald', 'rose', 'amber', 'violet', 'cyan'];

function applyAccent(accent) {
    const selected = availableAccents.includes(accent) ? accent : 'indigo';
    document.documentElement.dataset.accent = selected;

    document.querySelectorAll('[data-accent-option]').forEach((option) => {
        option.setAttribute('aria-pressed', String(option.dataset.accentOption === selected));
    });
}

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
const savedAccent = localStorage.getItem(accentKey);
applyTheme(savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
applyAccent(savedAccent || 'indigo');

document.addEventListener('DOMContentLoaded', () => {
    applyTheme(document.documentElement.dataset.theme || 'light');
    applyAccent(localStorage.getItem(accentKey) || 'indigo');

    document.querySelectorAll('[data-accent-option]').forEach((option) => {
        option.addEventListener('click', () => {
            const accent = option.dataset.accentOption;
            if (!availableAccents.includes(accent)) return;
            localStorage.setItem(accentKey, accent);
            applyAccent(accent);
        });
    });

    document.getElementById('theme-toggle')?.addEventListener('click', () => {
        const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        localStorage.setItem(themeKey, nextTheme);
        applyTheme(nextTheme);
    });
});
