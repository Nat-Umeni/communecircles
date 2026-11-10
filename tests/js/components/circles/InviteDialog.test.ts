import { mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import InviteDialog from '@/components/circles/InviteDialog.vue';
import { fakeRouter, field, lastPostOptions, visit } from '@tests/support';

function inviteDialog(): VueWrapper {
    return mount(InviteDialog, { props: { circle: 'abc123', open: true }, attachTo: document.body });
}

function dialog(wrapper: VueWrapper): HTMLDialogElement {
    return wrapper.get<HTMLDialogElement>('dialog').element;
}

function button(wrapper: VueWrapper, label: string) {
    return wrapper.findAll('button').find((found) => found.text() === label)!;
}

async function invite(wrapper: VueWrapper, email: string): Promise<void> {
    await field(wrapper, 'email').setValue(email);
    await wrapper.get('form').trigger('submit');
}

describe('InviteDialog', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('asks for the email address of the person to invite', () => {
        const wrapper = inviteDialog();

        expect(dialog(wrapper).open).toBe(true);
        expect(wrapper.get('dialog h2').text()).toBe('Invite someone');
        expect(wrapper.get('dialog').text()).toContain("They'll need an account already.");
        expect(button(wrapper, 'Send invite').attributes('type')).toBe('submit');
        expect(button(wrapper, 'Send invite').attributes('form')).toBe(wrapper.get('form').attributes('id'));
    });

    it('invites someone to the circle by their email address', async () => {
        const wrapper = inviteDialog();

        await invite(wrapper, 'grace@example.com');

        expect(fakeRouter.post).toHaveBeenCalledWith('/circles.invites.store/abc123', { email: 'grace@example.com' }, expect.objectContaining({ preserveScroll: true }));
    });

    it('clears the email address and closes once the invite is sent', async () => {
        const wrapper = inviteDialog();

        await invite(wrapper, 'grace@example.com');
        lastPostOptions().onSuccess({} as never);
        await wrapper.vm.$nextTick();

        expect(field(wrapper, 'email').element.value).toBe('');
        expect(dialog(wrapper).open).toBe(false);
        expect(wrapper.emitted('update:open')).toEqual([[false]]);
    });

    it("shows why the invite wasn't sent, and stays open", async () => {
        const wrapper = inviteDialog();

        await invite(wrapper, 'grace@example.com');
        await visit.fail(wrapper, { email: "They're already in this circle." });

        expect(wrapper.text()).toContain("They're already in this circle.");
        expect(field(wrapper, 'email').attributes('aria-invalid')).toBe('true');
        expect(dialog(wrapper).open).toBe(true);
    });

    it('waits for the invite to be sent', async () => {
        const wrapper = inviteDialog();

        await invite(wrapper, 'grace@example.com');
        await visit.start(wrapper);

        expect(button(wrapper, 'Send invite').attributes('disabled')).toBeDefined();
    });

    it('closes from Cancel without inviting anyone, and tells the page', async () => {
        const wrapper = inviteDialog();

        await button(wrapper, 'Cancel').trigger('click');

        expect(dialog(wrapper).open).toBe(false);
        expect(wrapper.emitted('update:open')).toEqual([[false]]);
        expect(fakeRouter.post).not.toHaveBeenCalled();
    });

    it('closes from its own close button and tells the page, so the page can open it again', async () => {
        const wrapper = inviteDialog();

        await wrapper.get('dialog button[aria-label="Close"]').trigger('click');

        expect(dialog(wrapper).open).toBe(false);
        expect(wrapper.emitted('update:open')).toEqual([[false]]);

        await wrapper.setProps({ open: false });
        await wrapper.setProps({ open: true });

        expect(dialog(wrapper).open).toBe(true);
    });
});
