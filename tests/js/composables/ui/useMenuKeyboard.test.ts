import { afterEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { useMenuKeyboard } from '@/composables/ui/useMenuKeyboard';

/**
 * A menu's panel of three items in the page, with the composable working on it.
 */
function menu(open = true) {
    const panel = document.createElement('div');
    panel.innerHTML = '<button role="menuitem">One</button><button role="menuitem">Two</button><button role="menuitem">Three</button>';
    document.body.append(panel);
    const close = vi.fn();

    return { items: Array.from(panel.querySelectorAll('button')), close, ...useMenuKeyboard(ref(panel), ref(open), close) };
}

function press(key: string): KeyboardEvent {
    return new KeyboardEvent('keydown', { key, cancelable: true });
}

describe('useMenuKeyboard', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('focuses an item by its position, wrapping around at either end', () => {
        const { items, focusItem } = menu();

        focusItem(0);
        expect(document.activeElement).toBe(items[0]);

        focusItem(-1);
        expect(document.activeElement).toBe(items[2]);

        focusItem(3);
        expect(document.activeElement).toBe(items[0]);
    });

    it('moves down and up through the items with the arrow keys, wrapping at the ends', () => {
        const { items, focusItem, onKeydown } = menu();
        const down = press('ArrowDown');

        focusItem(0);
        onKeydown(down);
        expect(document.activeElement).toBe(items[1]);
        expect(down.defaultPrevented).toBe(true);

        focusItem(2);
        onKeydown(press('ArrowDown'));
        expect(document.activeElement).toBe(items[0]);

        onKeydown(press('ArrowUp'));
        expect(document.activeElement).toBe(items[2]);
    });

    it('jumps to the first and last items with Home and End', () => {
        const { items, focusItem, onKeydown } = menu();

        focusItem(1);
        onKeydown(press('End'));
        expect(document.activeElement).toBe(items[2]);

        onKeydown(press('Home'));
        expect(document.activeElement).toBe(items[0]);
    });

    it('closes the menu with Escape', () => {
        const { close, onKeydown } = menu();
        const escape = press('Escape');

        onKeydown(escape);

        expect(close).toHaveBeenCalledOnce();
        expect(escape.defaultPrevented).toBe(true);
    });

    it('leaves other keys alone', () => {
        const { close, onKeydown } = menu();
        const letter = press('a');

        onKeydown(letter);

        expect(letter.defaultPrevented).toBe(false);
        expect(close).not.toHaveBeenCalled();
    });

    it('does nothing while the menu is closed', () => {
        const { items, close, focusItem, onKeydown } = menu(false);
        const down = press('ArrowDown');

        focusItem(0);
        onKeydown(down);
        onKeydown(press('Escape'));

        expect(document.activeElement).toBe(items[0]);
        expect(down.defaultPrevented).toBe(false);
        expect(close).not.toHaveBeenCalled();
    });
});
