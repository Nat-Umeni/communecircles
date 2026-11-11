import type { Ref } from 'vue';

/**
 * Keyboard use of a menu's [role="menuitem"]s while it's open: the arrow keys move between them, wrapping at either
 * end, Home and End jump to the first and last, and Escape calls close(). Other keys are left alone.
 */
export function useMenuKeyboard(
    panel: Ref<HTMLElement | null>,
    open: Ref<boolean>,
    close: () => void,
): { focusItem: (index: number) => void; onKeydown: (event: KeyboardEvent) => void } {
    function items(): HTMLElement[] {
        return Array.from(panel.value?.querySelectorAll<HTMLElement>('[role="menuitem"]') ?? []);
    }

    /** Focus the item at the given position, wrapping around at either end. */
    function focusItem(index: number): void {
        const list = items();
        list[(index + list.length) % list.length]?.focus();
    }

    function onKeydown(event: KeyboardEvent): void {
        if (!open.value) {
            return;
        }

        const current = items().indexOf(document.activeElement as HTMLElement);

        switch (event.key) {
            case 'Escape':
                event.preventDefault();
                close();
                break;
            case 'ArrowDown':
                event.preventDefault();
                focusItem(current + 1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                focusItem(current - 1);
                break;
            case 'Home':
                event.preventDefault();
                focusItem(0);
                break;
            case 'End':
                event.preventDefault();
                focusItem(-1);
                break;
        }
    }

    return { focusItem, onKeydown };
}
