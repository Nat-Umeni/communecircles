import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { page } from '../support/page';

describe('AuthLayout', () => {
    it('shows the heading, description and the form in a card, with the logo linking home', () => {
        const wrapper = mount(AuthLayout, {
            props: { title: 'Welcome back', description: 'Log in to pick up where you left off.' },
            slots: { default: '<form>Form</form>' },
        });

        expect(wrapper.get('h1').text()).toBe('Welcome back');
        expect(wrapper.get('main header p').text()).toBe('Log in to pick up where you left off.');
        expect(wrapper.get('section').html()).toContain('<form>Form</form>');
        expect(wrapper.get('a').attributes('href')).toBe('/home');
    });

    it('leaves out the description and footer when there are none', () => {
        const wrapper = mount(AuthLayout, { props: { title: 'Check your inbox' } });

        expect(wrapper.find('main header p').exists()).toBe(false);
        expect(wrapper.find('p.text-center').exists()).toBe(false);
        expect(wrapper.find('[role="status"]').exists()).toBe(false);
    });

    it('shows links under the card', () => {
        const wrapper = mount(AuthLayout, { props: { title: 'Welcome back' }, slots: { footer: 'New here? Join the Circle' } });

        expect(wrapper.get('p.text-center').text()).toBe('New here? Join the Circle');
    });

    it('shows a flashed message above the card', () => {
        page.flash.status = 'Your password has been reset. You can log in with it now.';

        expect(mount(AuthLayout, { props: { title: 'Welcome back' } }).get('[role="status"]').text()).toBe('Your password has been reset. You can log in with it now.');
    });
});
