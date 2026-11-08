import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ForgotPassword from '@/pages/auth/ForgotPassword.vue';
import { visit } from '../../support/form';
import { fakeRouter } from '../../support/router';

describe('ForgotPassword', () => {
    it('asks for the email address, with a link back to log in', () => {
        const wrapper = mount(ForgotPassword);

        expect(wrapper.get('h1').text()).toBe('Forgot your password?');
        expect(wrapper.get('[name="email"]').attributes('type')).toBe('email');
        expect(wrapper.findAll('a').map((link) => [link.text(), link.attributes('href')])).toContainEqual(['Log in', '/login']);
    });

    it('asks for a reset link for the email typed in, showing that it is sending', async () => {
        const wrapper = mount(ForgotPassword);
        await wrapper.get('[name="email"]').setValue('ada@example.com');

        await wrapper.get('form').trigger('submit');

        expect(fakeRouter.post).toHaveBeenCalledWith('/password.email', { email: 'ada@example.com' }, expect.any(Object));

        await visit.start(wrapper);
        expect(wrapper.get('button[type="submit"]').text()).toBe('Sending…');

        await visit.finish(wrapper);
        expect(wrapper.get('button[type="submit"]').text()).toBe('Email me a reset link');
    });

    it('shows how long to wait after too many attempts', async () => {
        const wrapper = mount(ForgotPassword);
        await wrapper.get('form').trigger('submit');

        await visit.throttle(wrapper, 45);

        expect(wrapper.text()).toContain('Too many attempts. Please try again in 45 seconds.');
    });
});
