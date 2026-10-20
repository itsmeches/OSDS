import './applicant.js';

const themeStorageKey = 'osds-theme';

function storedTheme() {
    try {
        return localStorage.getItem(themeStorageKey) || 'system';
    } catch (_) {
        return document.documentElement.dataset.theme || 'system';
    }
}

function applyTheme(preference) {
    const useDark = preference === 'dark'
        || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

    document.documentElement.classList.toggle('dark', useDark);
    document.documentElement.dataset.theme = preference;

    document.querySelectorAll('[data-theme-option]').forEach((button) => {
        button.setAttribute('aria-pressed', button.dataset.themeOption === preference ? 'true' : 'false');
    });
}

applyTheme(storedTheme());

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-theme-option]');
    if (! button) return;

    try { localStorage.setItem(themeStorageKey, button.dataset.themeOption); } catch (_) {}
    applyTheme(button.dataset.themeOption);
});

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (storedTheme() === 'system') applyTheme('system');
});
