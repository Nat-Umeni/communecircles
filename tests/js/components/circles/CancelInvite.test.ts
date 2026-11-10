import { mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import CancelInvite from '@/components/circles/CancelInvite.vue';
import { fakeRouter } from '@tests/support';

function cancelInvite(): VueWrapper {
    return mount(CancelInvite, { props: { invite: 9, name: 'Alan Turing' }, attachTo: document.body });
}

function dialog(wrapper: VueWrapper): HTMLDialogElement {
    return wrapper.get<HTMLDialogElement>('dialog').element;
}

function button(wrapper: VueWrapper, label: string) {
    return wrapper.findAll('button').find((found) => found.text() === label)!;
}

function lastCancel() {
    return fakeRouter.post.mock.lastCall!;
}

describe('CancelInvite', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('cancels the invite once that is confirmed', async () => {
        const wrapper = cancelInvite();

        expect(button(wrapper, 'Cancel').attributes('aria-label')).toBe("Cancel Alan Turing's invite");

        await button(wrapper, 'Cancel').trigger('click');

        expect(dialog(wrapper).open).toBe(true);
        expect(wrapper.get('dialog h2').text()).toBe("Cancel Alan Turing's invite?");
        expect(wrapper.get('dialog').text()).toContain("They won't be able to accept it. You can invite them again later.");
        expect(fakeRouter.post).not.toHaveBeenCalled();

        await button(wrapper, 'Cancel invite').trigger('click');

        expect(lastCancel()[0]).toBe('/approvals.cancel/9');
        expect(lastCancel()[2]).toEqual(expect.objectContaining({ preserveScroll: true }));
    });

    it('closes once the invite is cancelled', async () => {
        const wrapper = cancelInvite();

        await button(wrapper, 'Cancel').trigger('click');
        await button(wrapper, 'Cancel invite').trigger('click');
        lastCancel()[2].onSuccess({});
        await wrapper.vm.$nextTick();

        expect(dialog(wrapper).open).toBe(false);
    });

    it('waits for the invite to be cancelled', async () => {
        const wrapper = cancelInvite();

        await button(wrapper, 'Cancel').trigger('click');
        await button(wrapper, 'Cancel invite').trigger('click');
        lastCancel()[2].onStart();
        await wrapper.vm.$nextTick();

        expect(button(wrapper, 'Cancel invite').attributes('disabled')).toBeDefined();
    });

    it('keeps the invite from "Keep it"', async () => {
        const wrapper = cancelInvite();

        await button(wrapper, 'Cancel').trigger('click');
        await button(wrapper, 'Keep it').trigger('click');

        expect(dialog(wrapper).open).toBe(false);
        expect(fakeRouter.post).not.toHaveBeenCalled();
    });

    it("closes from the dialog's own close button and opens again", async () => {
        const wrapper = cancelInvite();

        await button(wrapper, 'Cancel').trigger('click');
        await wrapper.get('dialog button[aria-label="Close"]').trigger('click');

        expect(dialog(wrapper).open).toBe(false);

        await button(wrapper, 'Cancel').trigger('click');

        expect(dialog(wrapper).open).toBe(true);
    });
});
