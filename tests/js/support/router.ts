import { vi } from 'vitest';

/**
 * Stands in for Inertia's router (from @inertiajs/core), so links and forms record what they would
 * request instead of sending it. useForm().post() lands in router.post(url, data, options).
 */
export const fakeRouter = {
    visit: vi.fn(),
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    reload: vi.fn(),
    prefetch: vi.fn(),
    remember: vi.fn(),
    restore: vi.fn(),
    on: vi.fn(() => () => {}),
    getPrefetching: vi.fn(),
    getCached: vi.fn(),
    flush: vi.fn(),
    cancelAll: vi.fn(),
};

/**
 * The options object the last router.post() call was given, e.g. to run its onFinish callback.
 */
export function lastPostOptions(): Record<string, (...args: never[]) => unknown> {
    const call = fakeRouter.post.mock.lastCall;

    if (!call) {
        throw new Error('Nothing was posted.');
    }

    return call[2];
}
