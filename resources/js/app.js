import * as bootstrap from 'bootstrap';
import Alpine from 'alpinejs';

window.bootstrap = bootstrap;
window.Alpine = Alpine;

const THEME_KEY = 'eams-theme';
const ACCENT_KEY = 'eams-accent';
const MODES = ['light', 'dark', 'system'];
const ACCENTS = ['indigo', 'emerald', 'violet', 'amber', 'rose', 'ocean'];

const prefersDark = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

function readStored(key, fallback, allowed) {
    try {
        const value = localStorage.getItem(key);

        return allowed.includes(value) ? value : fallback;
    } catch (error) {
        // Private browsing / storage dimatikan tidak boleh mematikan UI.
        return fallback;
    }
}

function writeStored(key, value) {
    try {
        localStorage.setItem(key, value);
    } catch (error) {
        // Diabaikan dengan sengaja.
    }
}

/* -----------------------------------------------------------------------------
 * Store tema: mode terang/gelap/ikut sistem + 6 pilihan warna aksen.
 * -------------------------------------------------------------------------- */
Alpine.store('theme', {
    modes: MODES,
    accents: ACCENTS,
    mode: readStored(THEME_KEY, 'system', MODES),
    accent: readStored(ACCENT_KEY, 'indigo', ACCENTS),

    init() {
        this.apply();

        if (prefersDark && typeof prefersDark.addEventListener === 'function') {
            prefersDark.addEventListener('change', () => {
                if (this.mode === 'system') {
                    this.apply();
                }
            });
        }
    },

    get resolved() {
        if (this.mode === 'system') {
            return prefersDark && prefersDark.matches ? 'dark' : 'light';
        }

        return this.mode;
    },

    get icon() {
        if (this.mode === 'system') {
            return 'bi-circle-half';
        }

        return this.resolved === 'dark' ? 'bi-moon-stars-fill' : 'bi-sun-fill';
    },

    setMode(mode) {
        if (!MODES.includes(mode)) {
            return;
        }

        this.mode = mode;
        writeStored(THEME_KEY, mode);
        this.apply();
    },

    setAccent(accent) {
        if (!ACCENTS.includes(accent)) {
            return;
        }

        this.accent = accent;
        writeStored(ACCENT_KEY, accent);
        this.apply();
    },

    toggle() {
        this.setMode(this.resolved === 'dark' ? 'light' : 'dark');
    },

    apply() {
        const root = document.documentElement;

        root.setAttribute('data-bs-theme', this.resolved);
        root.setAttribute('data-eams-accent', this.accent);
    },
});

/* -----------------------------------------------------------------------------
 * Store toast: notifikasi ringan yang dipakai flash session maupun JavaScript.
 * -------------------------------------------------------------------------- */
const TOAST_PRESETS = {
    success: { icon: 'bi-check-circle-fill', title: 'Berhasil' },
    error: { icon: 'bi-x-circle-fill', title: 'Gagal' },
    warning: { icon: 'bi-exclamation-triangle-fill', title: 'Perhatian' },
    info: { icon: 'bi-info-circle-fill', title: 'Info' },
};

Alpine.store('toasts', {
    items: [],
    lastId: 0,

    push(payload = {}) {
        const type = TOAST_PRESETS[payload.type] ? payload.type : 'info';
        const message = String(payload.message ?? '').trim();

        if (message === '') {
            return null;
        }

        const timeout = Number.isFinite(payload.timeout) ? payload.timeout : 5000;
        const toast = {
            id: ++this.lastId,
            type,
            icon: TOAST_PRESETS[type].icon,
            title: payload.title ?? TOAST_PRESETS[type].title,
            message,
            timeout,
        };

        this.items.push(toast);

        if (timeout > 0) {
            setTimeout(() => this.remove(toast.id), timeout);
        }

        return toast.id;
    },

    remove(id) {
        this.items = this.items.filter((toast) => toast.id !== id);
    },

    clear() {
        this.items = [];
    },

    success(message, options = {}) {
        return this.push({ ...options, type: 'success', message });
    },

    error(message, options = {}) {
        return this.push({ ...options, type: 'error', message });
    },

    warning(message, options = {}) {
        return this.push({ ...options, type: 'warning', message });
    },

    info(message, options = {}) {
        return this.push({ ...options, type: 'info', message });
    },
});

/* Helper global supaya skrip non-Alpine tetap bisa memunculkan toast. */
window.eamsToast = (message, type = 'info', options = {}) =>
    Alpine.store('toasts').push({ ...options, type, message });

window.addEventListener('eams:toast', (event) => {
    const detail = event.detail ?? {};

    Alpine.store('toasts').push(
        typeof detail === 'string' ? { message: detail } : detail
    );
});

/* Kompatibilitas mundur: komponen lama masih memakai x-data="themeSwitcher". */
Alpine.data('themeSwitcher', () => ({
    get theme() {
        return Alpine.store('theme').resolved;
    },

    toggle() {
        Alpine.store('theme').toggle();
    },
}));

Alpine.start();
