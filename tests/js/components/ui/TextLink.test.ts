import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import TextLink from '@/components/ui/TextLink.vue';
import { fakeRouter } from '@tests/support';

describe('TextLink', () => {
    it('renders an app link that navigates through Inertia', async () => {
        const wrapper = mount(TextLink, { props: { href: '/login' }, slots: { default: 'Log in' } });

        expect(wrapper.element.tagName).toBe('A');
        expect(wrapper.attributes('href')).toBe('/login');

        await wrapper.trigger('click');

        expect(fakeRouter.visit).toHaveBeenCalledWith('/login', expect.objectContaining({ method: 'get' }));
    });

    it('renders a button that sends the request when given another method', async () => {
        const wrapper = mount(TextLink, { props: { href: '/logout', method: 'post' }, slots: { default: 'Log out' } });

        expect(wrapper.element.tagName).toBe('BUTTON');

        await wrapper.trigger('click');

        expect(fakeRouter.visit).toHaveBeenCalledWith('/logout', expect.objectContaining({ method: 'post' }));
    });

    it('renders a plain link for pages outside the app', async () => {
        const wrapper = mount(TextLink, { props: { href: 'https://example.com/rules', external: true }, slots: { default: 'Community rules' } });

        expect(wrapper.element.tagName).toBe('A');
        expect(wrapper.attributes('href')).toBe('https://example.com/rules');

        await wrapper.trigger('click');

        expect(fakeRouter.visit).not.toHaveBeenCalled();
    });
});
