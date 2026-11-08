import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import AppHeader from '@/components/AppHeader.vue';

describe('AppHeader', () => {
    it('opens the sidebar drawer from the menu button', async () => {
        const wrapper = mount(AppHeader, { props: { sidebarOpen: false } });
        const button = wrapper.get('button[aria-controls="app-sidebar"]');

        expect(button.attributes('aria-expanded')).toBe('false');
        expect(button.text()).toBe('Open menu');

        await button.trigger('click');

        expect(wrapper.emitted('update:sidebarOpen')).toEqual([[true]]);
    });

    it('offers to close the drawer while it is open', async () => {
        const wrapper = mount(AppHeader, { props: { sidebarOpen: true } });
        const button = wrapper.get('button[aria-controls="app-sidebar"]');

        expect(button.attributes('aria-expanded')).toBe('true');
        expect(button.text()).toBe('Close menu');

        await button.trigger('click');

        expect(wrapper.emitted('update:sidebarOpen')).toEqual([[false]]);
    });

    it('links the logo home and has the theme toggle', () => {
        const wrapper = mount(AppHeader, { props: { sidebarOpen: false } });

        expect(wrapper.get('a').attributes('href')).toBe('/home');
        expect(wrapper.get('[role="group"]').attributes('aria-label')).toBe('Colour theme');
    });
});
