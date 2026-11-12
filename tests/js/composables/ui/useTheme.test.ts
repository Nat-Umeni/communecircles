import { afterEach, describe, expect, it, vi } from 'vitest';

// useTheme keeps its state in the module, so each test loads a fresh copy.
async function freshUseTheme() {
    vi.resetModules();

    return (await import('@/composables/ui/useTheme')).useTheme;
}

/** Stands in for the system's light/dark preference, and lets a test change it. */
function systemPrefersDark(dark: boolean) {
    const listeners: Array<() => void> = [];
    const query = { matches: dark, addEventListener: vi.fn((_event: string, listener: () => void) => listeners.push(listener)) };
    vi.spyOn(window, 'matchMedia').mockReturnValue(query as unknown as MediaQueryList);

    return (nowDark: boolean) => {
        query.matches = nowDark;
        listeners.forEach((listener) => listener());
    };
}

/**
 * Replaces localStorage with one that throws, as in private browsing with storage blocked.
 * NOTE: spying on Storage.prototype isn't enough here: happy-dom keeps using a storage method once it has been called.
 */
function blockStorage() {
    const blocked = () => {
        throw new Error('blocked');
    };
    const storage = { getItem: vi.fn(blocked), setItem: vi.fn(blocked), removeItem: vi.fn(blocked) };
    vi.stubGlobal('localStorage', storage);

    return storage;
}

const isDark = () => document.documentElement.classList.contains('dark');

afterEach(() => {
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
    document.documentElement.classList.remove('dark');
});

describe('useTheme', () => {
    it('follows the system when nothing is saved', async () => {
        systemPrefersDark(false);

        const { theme } = (await freshUseTheme())();

        expect(theme.value).toBe('system');
    });

    it('starts from the saved theme', async () => {
        systemPrefersDark(false);
        localStorage.setItem('theme', 'dark');

        const { theme } = (await freshUseTheme())();

        expect(theme.value).toBe('dark');
    });

    it('ignores a saved value that is not a theme', async () => {
        systemPrefersDark(false);
        localStorage.setItem('theme', 'purple');

        const { theme } = (await freshUseTheme())();

        expect(theme.value).toBe('system');
    });

    it('follows the system when storage is blocked', async () => {
        systemPrefersDark(false);
        const storage = blockStorage();

        const { theme } = (await freshUseTheme())();

        expect(storage.getItem).toHaveBeenCalledWith('theme');
        expect(theme.value).toBe('system');
    });

    it('saves a chosen light or dark theme and applies it to the page', async () => {
        systemPrefersDark(false);
        const { theme } = (await freshUseTheme())();

        theme.value = 'dark';
        expect(localStorage.getItem('theme')).toBe('dark');
        expect(isDark()).toBe(true);

        theme.value = 'light';
        expect(localStorage.getItem('theme')).toBe('light');
        expect(isDark()).toBe(false);
    });

    it('forgets the saved theme when going back to the system setting, and follows the system', async () => {
        systemPrefersDark(true);
        localStorage.setItem('theme', 'light');
        const { setTheme, theme } = (await freshUseTheme())();

        setTheme('system');

        expect(theme.value).toBe('system');
        expect(localStorage.getItem('theme')).toBeNull();
        expect(isDark()).toBe(true);
    });

    it('treats an unknown choice as the system setting', async () => {
        systemPrefersDark(false);
        const { setTheme, theme } = (await freshUseTheme())();

        setTheme('purple');

        expect(theme.value).toBe('system');
    });

    it('still applies the choice when storage is blocked', async () => {
        systemPrefersDark(false);
        const { setTheme } = (await freshUseTheme())();
        const storage = blockStorage();

        setTheme('dark');
        expect(storage.setItem).toHaveBeenCalledWith('theme', 'dark');
        expect(isDark()).toBe(true);

        setTheme('system');
        expect(storage.removeItem).toHaveBeenCalledWith('theme');
        expect(isDark()).toBe(false);
    });

    it('follows changes to the system setting only while on the system setting', async () => {
        const changeSystem = systemPrefersDark(false);
        const { setTheme } = (await freshUseTheme())();

        changeSystem(true);
        expect(isDark()).toBe(true);

        setTheme('light');
        changeSystem(true);
        expect(isDark()).toBe(false);
    });

    it('says whether the page is dark now, following the system while on the system setting', async () => {
        const changeSystem = systemPrefersDark(false);
        const { dark, setTheme } = (await freshUseTheme())();

        expect(dark.value).toBe(false);

        changeSystem(true);
        expect(dark.value).toBe(true);

        setTheme('light');
        expect(dark.value).toBe(false);

        setTheme('dark');
        changeSystem(false);
        expect(dark.value).toBe(true);
    });

    it('shares one theme and one system listener between every caller', async () => {
        systemPrefersDark(false);
        const useTheme = await freshUseTheme();

        const first = useTheme();
        const second = useTheme();
        first.setTheme('dark');

        expect(second.theme.value).toBe('dark');
        expect(window.matchMedia('(prefers-color-scheme: dark)').addEventListener).toHaveBeenCalledTimes(1);
    });

    it('does nothing to the page while rendering on the server', async () => {
        const useTheme = await freshUseTheme();
        vi.stubGlobal('window', undefined);

        const { theme } = useTheme();

        expect(theme.value).toBe('system');
    });
});
