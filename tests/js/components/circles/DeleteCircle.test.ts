import { mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import DeleteCircle from '@/components/circles/DeleteCircle.vue';
import { fakeRouter } from '@tests/support';

function deleteCircle(): VueWrapper {
    return mount(DeleteCircle, { props: { circle: { public_id: 'abc123', name: 'Seed Exchange' } }, attachTo: document.body });
}

function button(wrapper: VueWrapper, label: string) {
    return wrapper.findAll('button').find((found) => found.text() === label)!;
}

describe('DeleteCircle', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('deletes the circle once that is confirmed', async () => {
        const wrapper = deleteCircle();

        await button(wrapper, 'Delete').trigger('click');

        expect(wrapper.get<HTMLDialogElement>('dialog').element.open).toBe(true);
        expect(wrapper.get('dialog h2').text()).toBe('Delete Seed Exchange?');
        expect(wrapper.get('dialog').text()).toContain("This can't be undone.");

        await button(wrapper, 'Delete the circle').trigger('click');

        expect(fakeRouter.delete).toHaveBeenCalledWith('/circles.destroy/abc123', expect.anything());
    });

    it('waits for the circle to be deleted', async () => {
        const wrapper = deleteCircle();

        await button(wrapper, 'Delete').trigger('click');
        await button(wrapper, 'Delete the circle').trigger('click');
        fakeRouter.delete.mock.lastCall![1].onStart();
        await wrapper.vm.$nextTick();

        expect(button(wrapper, 'Delete the circle').attributes('disabled')).toBeDefined();
    });

    it('closes the dialog from its own close button, deleting nothing, and opens it again', async () => {
        const wrapper = deleteCircle();

        await button(wrapper, 'Delete').trigger('click');
        await wrapper.get('dialog button[aria-label="Close"]').trigger('click');

        expect(wrapper.get<HTMLDialogElement>('dialog').element.open).toBe(false);
        expect(fakeRouter.delete).not.toHaveBeenCalled();

        await button(wrapper, 'Delete').trigger('click');

        expect(wrapper.get<HTMLDialogElement>('dialog').element.open).toBe(true);
    });

    it("doesn't delete anything when the dialog is cancelled", async () => {
        const wrapper = deleteCircle();

        await button(wrapper, 'Delete').trigger('click');
        await button(wrapper, 'Cancel').trigger('click');

        expect(wrapper.get<HTMLDialogElement>('dialog').element.open).toBe(false);
        expect(fakeRouter.delete).not.toHaveBeenCalled();
    });
});
