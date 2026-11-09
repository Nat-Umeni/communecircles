import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import InviteForm from '@/components/circles/InviteForm.vue';
import { fakeRouter, field, lastPostOptions, visit } from '@tests/support';

function inviteForm(): VueWrapper {
    return mount(InviteForm, { props: { circle: 'abc123' } });
}

async function invite(wrapper: VueWrapper, email: string): Promise<void> {
    await field(wrapper, 'email').setValue(email);
    await wrapper.get('form').trigger('submit');
}

describe('InviteForm', () => {
    it('invites someone to the circle by their email address', async () => {
        const wrapper = inviteForm();

        await invite(wrapper, 'grace@example.com');

        expect(fakeRouter.post).toHaveBeenCalledWith('/circles.invites.store/abc123', { email: 'grace@example.com' }, expect.objectContaining({ preserveScroll: true }));
    });

    it('clears the email address once the invite is sent', async () => {
        const wrapper = inviteForm();

        await invite(wrapper, 'grace@example.com');
        lastPostOptions().onSuccess({} as never);
        await wrapper.vm.$nextTick();

        expect(field(wrapper, 'email').element.value).toBe('');
    });

    it("shows why the invite wasn't sent", async () => {
        const wrapper = inviteForm();

        await invite(wrapper, 'grace@example.com');
        await visit.fail(wrapper, { email: "They're already in this circle." });

        expect(wrapper.text()).toContain("They're already in this circle.");
        expect(field(wrapper, 'email').attributes('aria-invalid')).toBe('true');
    });

    it('waits for the invite to be sent', async () => {
        const wrapper = inviteForm();

        await invite(wrapper, 'grace@example.com');
        await visit.start(wrapper);

        expect(wrapper.get('button[type="submit"]').attributes('disabled')).toBeDefined();
    });
});
