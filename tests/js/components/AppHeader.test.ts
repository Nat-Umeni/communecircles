import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import AppHeader from '@/components/AppHeader.vue';
import { signIn } from '@tests/support';

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

    it('offers members a Create menu that links to making a new circle', async () => {
        signIn();
        const wrapper = mount(AppHeader, { props: { sidebarOpen: false }, attachTo: document.body });

        await wrapper.get('[aria-label="Create"]').trigger('click');

        const item = wrapper.get('[role="menuitem"]');
        expect(item.text()).toBe('Create a circle');
        expect(item.attributes('href')).toBe('/circles.create');
        wrapper.unmount();
    });

    it("shows the page's own actions in the bar", () => {
        const wrapper = mount(AppHeader, { props: { sidebarOpen: false }, slots: { actions: '<a href="/edit" data-action>Edit</a>' } });

        expect(wrapper.get('header [data-action]').text()).toBe('Edit');
    });

    it('has no Create menu for guests', () => {
        const wrapper = mount(AppHeader, { props: { sidebarOpen: false } });

        expect(wrapper.find('[aria-label="Create"]').exists()).toBe(false);
    });

    it('links the logo home and has the theme toggle', () => {
        const wrapper = mount(AppHeader, { props: { sidebarOpen: false } });

        expect(wrapper.get('a').attributes('href')).toBe('/home');
        expect(wrapper.get('[role="group"]').attributes('aria-label')).toBe('Colour theme');
    });
});
