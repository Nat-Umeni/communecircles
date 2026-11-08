import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MenuItem from '@/components/ui/menu/MenuItem.vue';
import { fakeRouter } from '@tests/support';

describe('MenuItem', () => {
    it('is a menu item button, out of the tab order, without an href', () => {
        const wrapper = mount(MenuItem, { slots: { default: 'Create a circle' } });

        expect(wrapper.element.tagName).toBe('BUTTON');
        expect(wrapper.attributes()).toMatchObject({ type: 'button', role: 'menuitem', tabindex: '-1' });
        expect(wrapper.text()).toBe('Create a circle');
    });

    it.each([
        ['no method', undefined],
        ['GET', 'get'],
    ] as const)('is a link with %s', (_case, method) => {
        const wrapper = mount(MenuItem, { props: { href: '/settings', method }, slots: { default: 'Settings' } });

        expect(wrapper.element.tagName).toBe('A');
        expect(wrapper.attributes()).toMatchObject({ href: '/settings', role: 'menuitem', tabindex: '-1' });
    });

    it('is a button that sends the request for other methods', async () => {
        const wrapper = mount(MenuItem, { props: { href: '/logout', method: 'post' }, slots: { default: 'Log out' } });

        expect(wrapper.element.tagName).toBe('BUTTON');

        await wrapper.trigger('click');

        expect(fakeRouter.visit).toHaveBeenCalledWith('/logout', expect.objectContaining({ method: 'post' }));
    });
});
