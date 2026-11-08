import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ResetPassword from '@/pages/auth/ResetPassword.vue';
import { fakeRouter, field, links, visit } from '@tests/support';

function resetPassword() {
    return mount(ResetPassword, { props: { token: 'token-from-email', email: 'ada@example.com' } });
}

describe('ResetPassword', () => {
    it('fills in the email from the link and asks for the new password twice, with a link to ask for a new one', () => {
        const wrapper = resetPassword();

        expect(wrapper.get('h1').text()).toBe('Choose a new password');
        expect(field(wrapper, 'email').element.value).toBe('ada@example.com');
        expect(links(wrapper)).toContainEqual(['Ask for a new one', '/password.request']);
    });

    it('sets the new password with the token from the link', async () => {
        const wrapper = resetPassword();
        await field(wrapper, 'email').setValue('ada@example.org');
        await field(wrapper, 'password').setValue('Brand-new-pass1!');
        await field(wrapper, 'password_confirmation').setValue('Brand-new-pass1!');

        await wrapper.get('form').trigger('submit');

        expect(fakeRouter.post).toHaveBeenCalledWith(
            '/password.update',
            { token: 'token-from-email', email: 'ada@example.org', password: 'Brand-new-pass1!', password_confirmation: 'Brand-new-pass1!' },
            expect.any(Object),
        );
    });

    it('shows that it is saving, then clears both passwords', async () => {
        const wrapper = resetPassword();
        await field(wrapper, 'password').setValue('Brand-new-pass1!');
        await field(wrapper, 'password_confirmation').setValue('Brand-new-pass1!');
        await wrapper.get('form').trigger('submit');

        await visit.start(wrapper);
        expect(wrapper.get('button[type="submit"]').text()).toBe('Saving…');

        await visit.finish(wrapper);
        expect(field(wrapper, 'password').element.value).toBe('');
        expect(field(wrapper, 'password_confirmation').element.value).toBe('');
    });

    it('shows a problem with the link under the email, whether it is reported on the email or the token', async () => {
        const wrapper = resetPassword();
        await wrapper.get('form').trigger('submit');

        await visit.fail(wrapper, { token: 'The token field is required.' });
        expect(wrapper.text()).toContain('The token field is required.');

        await wrapper.get('form').trigger('submit');
        await visit.fail(wrapper, { email: 'This password reset link is invalid or has expired.' });
        expect(wrapper.text()).toContain('This password reset link is invalid or has expired.');
    });

    it('shows how long to wait after too many attempts', async () => {
        const wrapper = resetPassword();
        await wrapper.get('form').trigger('submit');

        await visit.throttle(wrapper, 20);

        expect(wrapper.text()).toContain('Too many attempts. Please try again in 20 seconds.');
    });
});
