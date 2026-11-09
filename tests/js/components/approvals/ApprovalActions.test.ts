import { mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import ApprovalActions from '@/components/approvals/ApprovalActions.vue';
import { fakeRouter, lastPostOptions, visit } from '@tests/support';

function actions(props: Record<string, unknown> = {}): VueWrapper {
    return mount(ApprovalActions, { props: { approval: 7, subject: 'Seed Exchange', ...props }, attachTo: document.body });
}

function button(wrapper: VueWrapper, label: string) {
    return wrapper.findAll('button').find((found) => found.text() === label)!;
}

function dialog(wrapper: VueWrapper): HTMLDialogElement {
    return wrapper.get<HTMLDialogElement>('dialog').element;
}

describe('ApprovalActions', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('approves the thing waiting for approval', async () => {
        const wrapper = actions();

        await button(wrapper, 'Approve').trigger('click');

        expect(fakeRouter.post).toHaveBeenCalledWith('/approvals.approve/7', {}, expect.objectContaining({ preserveScroll: true }));
    });

    it('rejects it with a reason, asked for in a dialog', async () => {
        const wrapper = actions();

        await button(wrapper, 'Reject').trigger('click');

        expect(dialog(wrapper).open).toBe(true);
        expect(wrapper.get('dialog h2').text()).toBe('Reject Seed Exchange?');

        await wrapper.get('dialog textarea[name="reason"]').setValue('There is already a circle for this nearby.');
        await wrapper.get('dialog form').trigger('submit');

        expect(fakeRouter.post).toHaveBeenCalledWith(
            '/approvals.reject/7',
            { reason: 'There is already a circle for this nearby.' },
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it("sends the rejection from the dialog's button", async () => {
        const wrapper = actions();

        await button(wrapper, 'Reject').trigger('click');
        const send = wrapper.findAll('dialog footer button').find((found) => found.text() === 'Reject')!;

        expect(send.attributes('type')).toBe('submit');
        expect(send.attributes('form')).toBe(wrapper.get('dialog form').attributes('id'));
    });

    it('closes the dialog and forgets the reason once rejected', async () => {
        const wrapper = actions();

        await button(wrapper, 'Reject').trigger('click');
        await wrapper.get('dialog textarea[name="reason"]').setValue('Too close to another circle.');
        await wrapper.get('dialog form').trigger('submit');
        lastPostOptions().onSuccess({} as never);
        await wrapper.vm.$nextTick();

        expect(dialog(wrapper).open).toBe(false);
        expect(wrapper.get<HTMLTextAreaElement>('dialog textarea[name="reason"]').element.value).toBe('');
    });

    it("shows why a rejection didn't go through", async () => {
        const wrapper = actions();

        await button(wrapper, 'Reject').trigger('click');
        await wrapper.get('dialog form').trigger('submit');
        await visit.fail(wrapper, { reason: 'The reason field must not be greater than 500 characters.' });

        expect(wrapper.get('dialog').text()).toContain('The reason field must not be greater than 500 characters.');
        expect(wrapper.get('dialog textarea[name="reason"]').attributes('aria-invalid')).toBe('true');
    });

    it('can be labelled for accepting and declining', async () => {
        const wrapper = actions({ approveLabel: 'Accept', rejectLabel: 'Decline', subject: "Ada Lovelace's request" });

        await button(wrapper, 'Decline').trigger('click');

        expect(button(wrapper, 'Accept').exists()).toBe(true);
        expect(wrapper.get('dialog h2').text()).toBe("Decline Ada Lovelace's request?");
    });

    it("doesn't reject anything when the dialog is cancelled", async () => {
        const wrapper = actions();

        await button(wrapper, 'Reject').trigger('click');
        await wrapper.get('dialog textarea[name="reason"]').setValue('Too close to another circle.');
        await button(wrapper, 'Cancel').trigger('click');

        expect(dialog(wrapper).open).toBe(false);
        expect(fakeRouter.post).not.toHaveBeenCalled();
    });

    it('closes the dialog from its own close button, rejecting nothing, and opens it again', async () => {
        const wrapper = actions();

        await button(wrapper, 'Reject').trigger('click');
        await wrapper.get('dialog button[aria-label="Close"]').trigger('click');

        expect(dialog(wrapper).open).toBe(false);
        expect(fakeRouter.post).not.toHaveBeenCalled();

        await button(wrapper, 'Reject').trigger('click');

        expect(dialog(wrapper).open).toBe(true);
    });

    it('waits for one decision to be sent before another', async () => {
        const wrapper = actions();

        await button(wrapper, 'Approve').trigger('click');
        await visit.start(wrapper);

        expect(button(wrapper, 'Approve').attributes('disabled')).toBeDefined();
        expect(button(wrapper, 'Reject').attributes('disabled')).toBeDefined();
    });

    it('waits for a rejection to be sent before another decision', async () => {
        const wrapper = actions();

        await button(wrapper, 'Reject').trigger('click');
        await wrapper.get('dialog form').trigger('submit');
        await visit.start(wrapper);

        const rejects = wrapper.findAll('button').filter((found) => found.text() === 'Reject');
        expect(button(wrapper, 'Approve').attributes('disabled')).toBeDefined();
        expect(rejects.map((found) => found.attributes('disabled'))).toEqual(['', '']);
    });
});
