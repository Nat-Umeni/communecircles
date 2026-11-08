import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import NavItem from '@/components/ui/NavItem.vue';
import { fakeRouter } from '@tests/support';

describe('NavItem', () => {
    it('links to its page with its icon and label', async () => {
        const wrapper = mount(NavItem, { props: { href: '/home', icon: 'circles' }, slots: { default: 'Circles' } });

        expect(wrapper.element.tagName).toBe('A');
        expect(wrapper.get('img').attributes('src')).toBe('/images/icons/circles.png');
        expect(wrapper.text()).toBe('Circles');

        await wrapper.trigger('click');

        expect(fakeRouter.visit).toHaveBeenCalledWith('/home', expect.anything());
    });

    it('marks the current page', () => {
        const active = mount(NavItem, { props: { href: '/home', icon: 'circles', active: true }, slots: { default: 'Circles' } });
        const other = mount(NavItem, { props: { href: '/home', icon: 'circles' }, slots: { default: 'Circles' } });

        expect(active.attributes('aria-current')).toBe('page');
        expect(active.classes()).toContain('bg-highlight');
        expect(other.attributes('aria-current')).toBeUndefined();
    });

    it('is plain text for a section without a page yet', () => {
        const wrapper = mount(NavItem, { props: { icon: 'explore' }, slots: { default: 'Explore' } });

        expect(wrapper.element.tagName).toBe('SPAN');
        expect(wrapper.attributes('href')).toBeUndefined();
    });
});
