import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import VerifyEmailBanner from '@/components/account/VerifyEmailBanner.vue';
import { signIn } from '../../support/page';
import { fakeRouter } from '../../support/router';

describe('VerifyEmailBanner', () => {
    it('shows nothing to guests', () => {
        expect(mount(VerifyEmailBanner).find('[role="status"]').exists()).toBe(false);
    });

    it('shows nothing once the email address is confirmed', () => {
        signIn({ email_verified: true });

        expect(mount(VerifyEmailBanner).find('[role="status"]').exists()).toBe(false);
    });

    it('asks an unconfirmed member to confirm, and can send the email again', async () => {
        signIn({ email: 'ada@example.com', email_verified: false });
        const wrapper = mount(VerifyEmailBanner);

        expect(wrapper.get('[role="status"]').text()).toContain('Please confirm your email address. We sent a link to ada@example.com.');

        await wrapper.get('button').trigger('click');

        expect(fakeRouter.visit).toHaveBeenCalledWith('/verification.send', expect.objectContaining({ method: 'post' }));
    });
});
