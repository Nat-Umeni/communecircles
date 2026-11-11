import { describe, expect, it, vi } from 'vitest';
import { useEventListener } from '@/composables/useEventListener';
import { withSetup } from '@tests/support';

describe('useEventListener', () => {
    it('listens on the window while its component is mounted, and stops once it is gone', () => {
        const listener = vi.fn();
        const { wrapper } = withSetup(() => useEventListener(window, 'keydown', listener));

        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));

        expect(listener).toHaveBeenCalledOnce();
        expect(listener.mock.calls[0][0].key).toBe('Escape');

        wrapper.unmount();
        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));

        expect(listener).toHaveBeenCalledOnce();
    });

    it('listens on the document too', () => {
        const listener = vi.fn();
        const { wrapper } = withSetup(() => useEventListener(document, 'pointerdown', listener));

        document.dispatchEvent(new Event('pointerdown'));
        wrapper.unmount();
        document.dispatchEvent(new Event('pointerdown'));

        expect(listener).toHaveBeenCalledOnce();
    });
});
