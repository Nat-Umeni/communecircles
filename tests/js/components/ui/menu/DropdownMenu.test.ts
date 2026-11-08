import { mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import DropdownMenu from '@/components/ui/menu/DropdownMenu.vue';
import MenuItem from '@/components/ui/menu/MenuItem.vue';

let wrapper: VueWrapper | undefined;

function menu(props: { side?: 'top' | 'bottom'; items?: string[] } = {}) {
    const items = props.items ?? ['Edit', 'Settings', 'Log out'];

    wrapper = mount({
        components: { DropdownMenu, MenuItem },
        setup: () => ({ items, side: props.side }),
        template: `
            <div>
                <DropdownMenu label="Account menu" :side="side">
                    <template #trigger>⚙</template>
                    <MenuItem v-for="item in items" :key="item">{{ item }}</MenuItem>
                </DropdownMenu>
                <button type="button" id="outside">Somewhere else</button>
            </div>`,
    }, { attachTo: document.body });

    return wrapper;
}

const trigger = () => wrapper!.get('[aria-haspopup="menu"]');
const panel = () => wrapper!.find('[role="menu"]');
const items = () => wrapper!.findAll('[role="menuitem"]');

afterEach(() => {
    wrapper?.unmount();
    wrapper = undefined;
});

describe('DropdownMenu', () => {
    it('is a closed menu button with an accessible name', () => {
        menu();

        expect(trigger().attributes()).toMatchObject({ 'aria-label': 'Account menu', 'aria-expanded': 'false' });
        expect(trigger().attributes('aria-controls')).toBeUndefined();
        expect(panel().exists()).toBe(false);
    });

    it('opens on click, focusing the first item, and closes on a second click', async () => {
        menu();

        await trigger().trigger('click');

        expect(trigger().attributes('aria-expanded')).toBe('true');
        expect(panel().attributes('aria-label')).toBe('Account menu');
        expect(trigger().attributes('aria-controls')).toBe(panel().attributes('id'));
        expect(document.activeElement).toBe(items()[0].element);

        await trigger().trigger('click');

        expect(panel().exists()).toBe(false);
    });

    it('opens downwards by default and upwards when asked', async () => {
        menu();
        await trigger().trigger('click');
        expect(panel().classes()).toContain('top-full');
        wrapper!.unmount();

        menu({ side: 'top' });
        await trigger().trigger('click');
        expect(panel().classes()).toContain('bottom-full');
    });

    it('moves between items with the arrow keys, Home and End, wrapping at either end', async () => {
        menu();
        await trigger().trigger('click');
        const focused = () => items().findIndex((item) => item.element === document.activeElement);

        await panel().trigger('keydown', { key: 'ArrowDown' });
        expect(focused()).toBe(1);

        await panel().trigger('keydown', { key: 'End' });
        expect(focused()).toBe(2);

        await panel().trigger('keydown', { key: 'ArrowDown' });
        expect(focused()).toBe(0);

        await panel().trigger('keydown', { key: 'ArrowUp' });
        expect(focused()).toBe(2);

        await panel().trigger('keydown', { key: 'Home' });
        expect(focused()).toBe(0);

        await panel().trigger('keydown', { key: 'a' });
        expect(focused()).toBe(0);
    });

    it('closes on Escape and puts focus back on the button', async () => {
        menu();
        await trigger().trigger('click');

        await panel().trigger('keydown', { key: 'Escape' });

        expect(panel().exists()).toBe(false);
        expect(document.activeElement).toBe(trigger().element);
    });

    it('ignores keys while closed', async () => {
        menu();

        await trigger().trigger('keydown', { key: 'Escape' });

        expect(panel().exists()).toBe(false);
        expect(trigger().attributes('aria-expanded')).toBe('false');
    });

    it('closes when an item is chosen, but not on a click elsewhere in the panel', async () => {
        menu();
        await trigger().trigger('click');

        await panel().trigger('click');
        expect(panel().exists()).toBe(true);

        await items()[1].trigger('click');
        expect(panel().exists()).toBe(false);
    });

    it('closes when focus moves outside, but not when it moves within or nowhere', async () => {
        menu();
        await trigger().trigger('click');

        await panel().trigger('focusout', { relatedTarget: items()[1].element });
        expect(panel().exists()).toBe(true);

        await panel().trigger('focusout', { relatedTarget: null });
        expect(panel().exists()).toBe(true);

        await panel().trigger('focusout', { relatedTarget: document.getElementById('outside') });
        expect(panel().exists()).toBe(false);
    });

    it('closes on a click outside, but not on a click inside', async () => {
        menu();
        await trigger().trigger('click');

        items()[0].element.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }));
        await wrapper!.vm.$nextTick();
        expect(panel().exists()).toBe(true);

        document.getElementById('outside')!.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }));
        await wrapper!.vm.$nextTick();
        expect(panel().exists()).toBe(false);
    });

    it('ignores clicks outside while closed, and stops listening once removed', async () => {
        menu();

        document.getElementById('outside')!.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }));
        expect(panel().exists()).toBe(false);

        wrapper!.unmount();
        wrapper = undefined;

        expect(() => document.body.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }))).not.toThrow();
    });

    it('ignores a key pressed before the opened menu has rendered', async () => {
        menu();

        (trigger().element as HTMLButtonElement).click();
        trigger().element.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));
        await wrapper!.vm.$nextTick();
        await wrapper!.vm.$nextTick();

        expect(panel().exists()).toBe(true);
        expect(document.activeElement).toBe(items()[0].element);
    });

    it('opens even with no items to focus', async () => {
        menu({ items: [] });

        await trigger().trigger('click');

        expect(panel().exists()).toBe(true);
        expect(document.activeElement).not.toBe(trigger().element);
    });
});
