import * as bootstrap from 'bootstrap';
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

window.bootstrap = bootstrap;
window.Alpine = Alpine;
window.Livewire = Livewire;

const THEME_KEY = 'eams-theme';
const ACCENT_KEY = 'eams-accent';
const SIDEBAR_KEY = 'eams-sidebar-collapsed';
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

function readStoredBoolean(key, fallback = false) {
    try {
        const value = localStorage.getItem(key);

        return value === null ? fallback : value === 'true';
    } catch (error) {
        return fallback;
    }
}

function writeStored(key, value) {
    try {
        localStorage.setItem(key, String(value));
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

/* State application shell. Collapse hanya berdampak di desktop; drawer mobile
 * tetap menampilkan label lengkap. */
Alpine.data('eamsShell', () => ({
    sidebarOpen: false,
    sidebarCollapsed: readStoredBoolean(SIDEBAR_KEY),
    userMenuOpen: false,

    toggleCollapsed() {
        this.sidebarCollapsed = ! this.sidebarCollapsed;
        writeStored(SIDEBAR_KEY, this.sidebarCollapsed);
    },

    closeOverlays() {
        this.sidebarOpen = false;
        this.userMenuOpen = false;
    },
}));

/* Repeater: baris dinamis untuk form multi-entry (Thermal, Kuesioner builder). */
/* Cascading select: dependent dropdown untuk Report/Print Center (fetch endpoint JSON). */
Alpine.data('eamsCascading', (sourceUrl, levels, initial = {}) => ({
    levels,
    values: { ...initial },
    cache: {},
    async ensureOptions(index) {
        const parentKey = index === 0 ? '' : this.levels[index - 1].name;
        const parentValue = index === 0 ? '' : (this.values[parentKey] || '');
        const key = index + ':' + parentValue;
        if (this.cache[key]) return;
        const url = new URL(sourceUrl, window.location.href);
        if (parentValue) url.searchParams.set('parent', parentValue);
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (! response.ok) throw new Error('Gagal memuat opsi ' + this.levels[index].label);
        this.cache[key] = await response.json();
    },
    optionsFor(index) {
        const parentKey = index === 0 ? '' : this.levels[index - 1].name;
        const parentValue = index === 0 ? '' : (this.values[parentKey] || '');
        return this.cache[index + ':' + parentValue] ?? [];
    },
    async onChange(index) {
        for (let i = index + 1; i < this.levels.length; i++) {
            this.values[this.levels[i].name] = '';
        }
        const next = index + 1;
        if (next < this.levels.length && this.values[this.levels[index].name]) {
            await this.ensureOptions(next);
        }
    },
    async init() {
        for (let i = 0; i < this.levels.length; i++) {
            const hasParent = i === 0 || !!this.values[this.levels[i - 1].name];
            if (! hasParent) break;
            await this.ensureOptions(i);
        }
    },
}));

Alpine.data('eamsRepeater', (template = {}, max = null) => ({
    rows: [{ ...template }],
    max,
    add() {
        if (this.max !== null && this.rows.length >= this.max) return;
        this.rows.push({ ...template });
    },
    remove(index) {
        if (this.rows.length <= 1) return;
        this.rows.splice(index, 1);
    },
}));

Alpine.data('eamsDropdown', () => ({
    open: false,
    toggle() {
        this.open = ! this.open;
    },
    close() {
        this.open = false;
    },
}));

/* -----------------------------------------------------------------------------
 * Navigasi: wire:navigate menjadi jalur utama. Interceptor klasik dipertahankan
 * hanya sebagai fallback untuk link GET internal yang belum dimigrasikan.
 * -------------------------------------------------------------------------- */
const root = document.documentElement;
const reduceMotion = window.matchMedia
    ? window.matchMedia('(prefers-reduced-motion: reduce)')
    : null;
let fallbackNavigationTimer = null;
let navigationSafetyTimer = null;

function resetNavigationState() {
    root.classList.remove('is-navigating');

    if (fallbackNavigationTimer !== null) {
        window.clearTimeout(fallbackNavigationTimer);
        fallbackNavigationTimer = null;
    }

    if (navigationSafetyTimer !== null) {
        window.clearTimeout(navigationSafetyTimer);
        navigationSafetyTimer = null;
    }
}

function beginNavigation() {
    root.classList.add('is-navigating');

    if (navigationSafetyTimer !== null) {
        window.clearTimeout(navigationSafetyTimer);
    }

    navigationSafetyTimer = window.setTimeout(resetNavigationState, 15000);
}

function canAnimateLink(event, link) {
    if (
        event.defaultPrevented
        || event.button !== 0
        || event.metaKey
        || event.ctrlKey
        || event.shiftKey
        || event.altKey
        || link.hasAttribute('wire:navigate')
        || link.hasAttribute('download')
        || link.hasAttribute('data-no-transition')
        || link.hasAttribute('data-bs-toggle')
        || link.hasAttribute('data-bs-dismiss')
        || (link.target && link.target.toLowerCase() !== '_self')
    ) {
        return false;
    }

    const rawHref = link.getAttribute('href');
    if (!rawHref || rawHref.startsWith('#') || rawHref.startsWith('javascript:')) {
        return false;
    }

    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin || !['http:', 'https:'].includes(url.protocol)) {
        return false;
    }

    if (url.pathname === window.location.pathname && url.search === window.location.search) {
        return false;
    }

    if (/\/(?:files|download|export)(?:\/|$)/.test(url.pathname) || /\/pdf(?:\/|$)/.test(url.pathname)) {
        return false;
    }

    return true;
}

window.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;

    if (!link || !canAnimateLink(event, link) || (reduceMotion && reduceMotion.matches)) {
        return;
    }

    event.preventDefault();

    if (root.classList.contains('is-navigating')) {
        return;
    }

    beginNavigation();
    fallbackNavigationTimer = window.setTimeout(() => {
        window.location.assign(link.href);
    }, 115);
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || (form.target && form.target !== '_self')) {
        return;
    }

    queueMicrotask(() => {
        if (!event.defaultPrevented) {
            beginNavigation();
        }
    });
});

document.addEventListener('livewire:navigate', beginNavigation);
document.addEventListener('livewire:navigating', (event) => {
    beginNavigation();
    event.detail?.onSwap?.(() => Alpine.store('theme').apply());
});
document.addEventListener('livewire:navigated', () => {
    Alpine.store('theme').apply();
    resetNavigationState();
    document.dispatchEvent(new CustomEvent('eams:page-ready'));
});

window.addEventListener('beforeunload', beginNavigation);
window.addEventListener('pageshow', resetNavigationState);

/* Livewire 4 menyertakan instance Alpine tunggal. Jangan panggil Alpine.start(). */
Livewire.start();
