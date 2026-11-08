import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Login from '@/pages/auth/Login.vue';
import { fakeRouter, field, links, visit } from '@tests/support';

describe('Login', () => {
    it('asks for an email, password and remember me, with links to reset a password or register', () => {
        const wrapper = mount(Login);

        expect(wrapper.get('h1').text()).toBe('Welcome back');
        expect(field(wrapper, 'email').attributes('type')).toBe('email');
        expect(field(wrapper, 'password').attributes('type')).toBe('password');
        expect(field(wrapper, 'remember').attributes('type')).toBe('checkbox');
        expect(links(wrapper)).toContainEqual(['Forgot your password?', '/password.request']);
        expect(links(wrapper)).toContainEqual(['Join the Circle', '/auth.register.create']);
    });

    it('logs in with the details typed in', async () => {
        const wrapper = mount(Login);
        await field(wrapper, 'email').setValue('ada@example.com');
        await field(wrapper, 'password').setValue('Analytical1!');
        await field(wrapper, 'remember').setValue(true);

        await wrapper.get('form').trigger('submit');

        expect(fakeRouter.post).toHaveBeenCalledWith('/login.store', { email: 'ada@example.com', password: 'Analytical1!', remember: true }, expect.any(Object));
    });

    it('shows that it is logging in, then clears the password but keeps the email', async () => {
        const wrapper = mount(Login);
        await field(wrapper, 'email').setValue('ada@example.com');
        await field(wrapper, 'password').setValue('Analytical1!');
        await wrapper.get('form').trigger('submit');

        await visit.start(wrapper);
        expect(wrapper.get('button[type="submit"]').text()).toBe('Logging in…');
        expect(wrapper.get('button[type="submit"]').attributes('disabled')).toBeDefined();

        await visit.finish(wrapper);
        expect(wrapper.get('button[type="submit"]').text()).toBe('Log in');
        expect(field(wrapper, 'password').element.value).toBe('');
        expect(field(wrapper, 'email').element.value).toBe('ada@example.com');
    });

    it('shows the error from the server under the email', async () => {
        const wrapper = mount(Login);
        await wrapper.get('form').trigger('submit');

        await visit.fail(wrapper, { email: 'These credentials do not match our records.' });

        expect(wrapper.text()).toContain('These credentials do not match our records.');
        expect(field(wrapper, 'email').attributes('aria-invalid')).toBe('true');
    });

    it('shows how long to wait after too many attempts', async () => {
        const wrapper = mount(Login);
        await wrapper.get('form').trigger('submit');

        await visit.throttle(wrapper, 30);

        expect(wrapper.text()).toContain('Too many attempts. Please try again in 30 seconds.');
    });
});
