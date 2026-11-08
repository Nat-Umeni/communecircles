import { config } from '@vue/test-utils';
import { afterEach, vi } from 'vitest';
import { page, resetPage } from './support/page';
import { fakeRoute } from './support/route';
import { fakeRouter } from './support/router';

// route() as imported in <script setup> (`import { route } from 'ziggy-js'`).
vi.mock('ziggy-js', async () => ({ route: (await import('./support/route')).fakeRoute }));

// Inertia's router records requests instead of sending them (see support/router.ts).
vi.mock('@inertiajs/core', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/core')>()),
    router: (await import('./support/router')).fakeRouter,
}));

// usePage() returns the test page (see support/page.ts), and <Head> renders nothing: the real one needs
// Inertia's head manager, which only exists in a running Inertia app (and it has no name to stub it by).
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const { defineComponent } = await import('vue');

    return {
        ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
        usePage: () => page,
        Head: defineComponent({ name: 'Head', props: { title: String }, render: () => null }),
    };
});

// route() and $page in templates, as ZiggyVue and Inertia provide them in the app.
config.global.mocks = {
    route: fakeRoute,
    $page: page,
};

afterEach(() => {
    resetPage();
    vi.clearAllMocks();
    fakeRouter.on.mockImplementation(() => () => {});
    localStorage.clear();
});
