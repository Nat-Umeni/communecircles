import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import VerifyEmail from '@/pages/auth/VerifyEmail.vue';
import { signIn } from '../../support/page';
import { visit } from '../../support/form';
import { fakeRouter } from '../../support/router';

// The only button in the card; its label changes while sending.
const resendButton = (wrapper: ReturnType<typeof mount>) => wrapper.get('main section button');

describe('VerifyEmail', () => {
    it('tells the member where the link was sent', () => {
        signIn({ email: 'ada@example.com', email_verified: false });

        const wrapper = mount(VerifyEmail);

        expect(wrapper.get('h1').text()).toBe('Check your inbox');
        expect(wrapper.text()).toContain("We've sent a link to ada@example.com.");
    });

    it('sends the email again, showing that it is sending', async () => {
        signIn({ email_verified: false });
        const wrapper = mount(VerifyEmail);

        await resendButton(wrapper).trigger('click');

        expect(fakeRouter.post).toHaveBeenCalledWith('/verification.send', {}, expect.any(Object));

        await visit.start(wrapper);
        expect(resendButton(wrapper).text()).toBe('Sending…');

        await visit.finish(wrapper);
        expect(resendButton(wrapper).text()).toBe('Send the email again');
    });

    it('shows how long to wait after asking too often, until the next try', async () => {
        signIn({ email_verified: false });
        const wrapper = mount(VerifyEmail);
        await resendButton(wrapper).trigger('click');

        await visit.throttle(wrapper, 50);
        expect(wrapper.text()).toContain('Too many attempts. Please try again in 50 seconds.');

        await resendButton(wrapper).trigger('click');
        expect(wrapper.text()).not.toContain('Too many attempts');
    });

    it('can log out to register with a different email', async () => {
        signIn({ email_verified: false });
        const wrapper = mount(VerifyEmail);

        await wrapper.findAll('button').find((button) => button.text() === 'Log out')!.trigger('click');

        expect(fakeRouter.visit).toHaveBeenCalledWith('/logout', expect.objectContaining({ method: 'post' }));
    });
});
