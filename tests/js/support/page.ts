import { reactive } from 'vue';
import type { AuthUser } from '@/types';

interface TestPage {
    component: string;
    props: {
        auth: { user: AuthUser | null };
        locale: string;
        locales: { value: string; label: string }[];
        errors: Record<string, string>;
        [key: string]: unknown;
    };
    flash: { status?: string };
}

/**
 * The languages HandleInertiaRequests shares, as the test page has them.
 */
export const locales = [
    { value: 'en', label: 'English' },
    { value: 'sw', label: 'Kiswahili' },
];

function freshPage(): TestPage {
    return {
        component: 'Home',
        props: {
            auth: { user: null },
            locale: 'en',
            locales: [...locales],
            errors: {},
        },
        flash: {},
    };
}

/**
 * The Inertia page the components see, through usePage() and $page. tests/js/setup.ts resets it after each test.
 */
export const page = reactive(freshPage());

export function resetPage(): void {
    Object.assign(page, freshPage());
}

/**
 * Log a member in for the current test, as the shared auth.user prop would.
 */
export function signIn(overrides: Partial<AuthUser> = {}): AuthUser {
    page.props.auth.user = {
        first_name: 'Ada',
        last_name: 'Lovelace',
        email: 'ada@example.com',
        email_verified: true,
        role: { value: 'user', label: 'User' },
        ...overrides,
    };

    return page.props.auth.user;
}
