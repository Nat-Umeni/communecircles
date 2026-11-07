import { computed, ref } from 'vue';

// Keep the storage key and logic in sync with the inline script in resources/views/app.blade.php.
const STORAGE_KEY = 'theme';
const THEMES = ['light', 'dark', 'system'];

const theme = ref('system');
let initialised = false;

function prefersDark() {
    return window.matchMedia('(prefers-color-scheme: dark)');
}

function readStoredTheme() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        return THEMES.includes(stored) ? stored : 'system';
    } catch {
        return 'system';
    }
}

function applyTheme() {
    const dark = theme.value === 'dark' || (theme.value === 'system' && prefersDark().matches);
    document.documentElement.classList.toggle('dark', dark);
}

function initialise() {
    if (initialised || typeof window === 'undefined') {
        return;
    }
    initialised = true;
    theme.value = readStoredTheme();
    prefersDark().addEventListener('change', () => {
        if (theme.value === 'system') {
            applyTheme();
        }
    });
}

function setTheme(value) {
    theme.value = THEMES.includes(value) ? value : 'system';
    try {
        if (theme.value === 'system') {
            localStorage.removeItem(STORAGE_KEY);
        } else {
            localStorage.setItem(STORAGE_KEY, theme.value);
        }
    } catch {
        // Storage can be blocked (private mode); the choice still applies for this page view.
    }
    applyTheme();
}

/**
 * Light / dark / system theme preference, shared by every component that uses it.
 * `theme` is writable, so it works with v-model.
 */
export function useTheme() {
    initialise();

    return {
        theme: computed({
            get: () => theme.value,
            set: setTheme,
        }),
        setTheme,
    };
}
