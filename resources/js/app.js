import * as bootstrap from 'bootstrap';
import Alpine from 'alpinejs';

window.bootstrap = bootstrap;
window.Alpine = Alpine;

Alpine.data('themeSwitcher', () => ({
    theme: document.documentElement.getAttribute('data-bs-theme') || 'light',

    init() {
        this.apply(this.theme, false);
    },

    toggle() {
        this.apply(this.theme === 'dark' ? 'light' : 'dark');
    },

    apply(theme, persist = true) {
        this.theme = theme;
        document.documentElement.setAttribute('data-bs-theme', theme);

        if (persist) {
            try {
                localStorage.setItem('eams-theme', theme);
            } catch (error) {
                // Private browsing/storage restrictions must not break the UI.
            }
        }
    },
}));

Alpine.start();
