import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Register from '@/pages/auth/Register.vue';
import { visit } from '../../support/form';
import { fakeRouter } from '../../support/router';

const field = (wrapper: ReturnType<typeof mount>, name: string) => wrapper.get(`[name="${name}"]`);

async function fillIn(wrapper: ReturnType<typeof mount>): Promise<void> {
    await field(wrapper, 'first_name').setValue('Ada');
    await field(wrapper, 'last_name').setValue('Lovelace');
    await field(wrapper, 'email').setValue('ada@example.com');
    await field(wrapper, 'password').setValue('Analytical1!');
    await field(wrapper, 'password_confirmation').setValue('Analytical1!');
}

describe('Register', () => {
    it('asks for a name, email and password, with the password rules and a link to log in', () => {
        const wrapper = mount(Register);

        expect(wrapper.get('h1').text()).toBe('Join the Circle');
        expect(wrapper.text()).toContain('At least 8 characters, with a letter, a number and a symbol.');
        expect(wrapper.findAll('a').map((link) => [link.text(), link.attributes('href')])).toContainEqual(['Log in', '/login']);
    });

    it('creates the account with the details typed in', async () => {
        const wrapper = mount(Register);
        await fillIn(wrapper);

        await wrapper.get('form').trigger('submit');

        expect(fakeRouter.post).toHaveBeenCalledWith(
            '/auth.register.store',
            { first_name: 'Ada', last_name: 'Lovelace', email: 'ada@example.com', password: 'Analytical1!', password_confirmation: 'Analytical1!' },
            expect.any(Object),
        );
    });

    it('shows that it is creating the account, then clears both passwords', async () => {
        const wrapper = mount(Register);
        await fillIn(wrapper);
        await wrapper.get('form').trigger('submit');

        await visit.start(wrapper);
        expect(wrapper.get('button[type="submit"]').text()).toBe('Creating your account…');

        await visit.finish(wrapper);
        expect((field(wrapper, 'password').element as HTMLInputElement).value).toBe('');
        expect((field(wrapper, 'password_confirmation').element as HTMLInputElement).value).toBe('');
        expect((field(wrapper, 'first_name').element as HTMLInputElement).value).toBe('Ada');
    });

    it('shows how long to wait after too many attempts', async () => {
        const wrapper = mount(Register);
        await wrapper.get('form').trigger('submit');

        await visit.throttle(wrapper, 12);

        expect(wrapper.text()).toContain('Too many attempts. Please try again in 12 seconds.');
    });
});
