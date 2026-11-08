import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import AppLayout from '@/layouts/AppLayout.vue';
import { page, signIn } from '@tests/support';

describe('AppLayout', () => {
    it('wraps the page in the header and sidebar', () => {
        const wrapper = mount(AppLayout, { slots: { default: '<p>Page content</p>' } });

        expect(wrapper.find('header').exists()).toBe(true);
        expect(wrapper.find('aside#app-sidebar').exists()).toBe(true);
        expect(wrapper.get('main').html()).toContain('<p>Page content</p>');
    });

    it('opens the sidebar from the header and closes it again from the sidebar', async () => {
        const wrapper = mount(AppLayout);
        const aside = () => wrapper.get('aside');

        await wrapper.get('button[aria-controls="app-sidebar"]').trigger('click');
        expect(aside().classes()).toContain('translate-x-0');

        await wrapper.get('[aria-hidden="true"].fixed').trigger('click');
        expect(aside().classes()).toContain('-translate-x-full');
    });

    it('shows a flashed message above the page', () => {
        page.flash.status = 'Thanks, your email address is confirmed.';

        expect(mount(AppLayout).get('main [role="status"]').text()).toBe('Thanks, your email address is confirmed.');
    });

    it('shows no message without one, and the confirm-email banner to unconfirmed members', () => {
        expect(mount(AppLayout).find('main [role="status"]').exists()).toBe(false);

        signIn({ email_verified: false });

        expect(mount(AppLayout).get('main [role="status"]').text()).toContain('Please confirm your email address.');
    });
});
