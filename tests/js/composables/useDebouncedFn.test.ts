import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useDebouncedFn } from '@/composables/useDebouncedFn';
import { withSetup } from '@tests/support';

describe('useDebouncedFn', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('runs once calls stop for the pause, with the last arguments', () => {
        const run = vi.fn();
        const { result } = withSetup(() => useDebouncedFn(run, 300));

        result.call('s');
        vi.advanceTimersByTime(200);
        result.call('se');
        vi.advanceTimersByTime(299);

        expect(run).not.toHaveBeenCalled();

        vi.advanceTimersByTime(1);

        expect(run).toHaveBeenCalledExactlyOnceWith('se');
    });

    it("doesn't run once it's cancelled", () => {
        const run = vi.fn();
        const { result } = withSetup(() => useDebouncedFn(run, 300));

        result.call();
        result.cancel();
        vi.advanceTimersByTime(300);

        expect(run).not.toHaveBeenCalled();
    });

    it("doesn't run after its component is gone", () => {
        const run = vi.fn();
        const { result, wrapper } = withSetup(() => useDebouncedFn(run, 300));

        result.call();
        wrapper.unmount();
        vi.advanceTimersByTime(300);

        expect(run).not.toHaveBeenCalled();
    });
});
