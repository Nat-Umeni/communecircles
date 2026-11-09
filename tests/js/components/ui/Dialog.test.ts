import { mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import Dialog from '@/components/ui/Dialog.vue';

let wrapper: VueWrapper | undefined;

function dialog(open: boolean) {
    wrapper = mount(Dialog, {
        props: { open, title: 'Delete this circle?' },
        slots: { default: '<p>This can’t be undone.</p>', actions: '<button type="button">Delete</button>' },
        attachTo: document.body,
    });

    return wrapper;
}

const element = () => wrapper!.get<HTMLDialogElement>('dialog').element;

afterEach(() => {
    wrapper?.unmount();
    wrapper = undefined;
});

describe('Dialog', () => {
    it('opens as a modal with its title, content and actions, labelled by its title', () => {
        dialog(true);

        expect(element().open).toBe(true);
        expect(wrapper!.get('h2').text()).toBe('Delete this circle?');
        expect(element().getAttribute('aria-labelledby')).toBe(wrapper!.get('h2').attributes('id'));
        expect(wrapper!.text()).toContain('This can’t be undone.');
        expect(wrapper!.get('footer button').text()).toBe('Delete');
    });

    it('stays closed until it is opened, and closes again when told to', async () => {
        dialog(false);
        expect(element().open).toBe(false);

        await wrapper!.setProps({ open: true });
        expect(element().open).toBe(true);

        await wrapper!.setProps({ open: false });
        expect(element().open).toBe(false);
    });

    it('asks to close from its close button', async () => {
        dialog(true);

        await wrapper!.get('button[aria-label="Close"]').trigger('click');

        expect(wrapper!.emitted('update:open')).toEqual([[false]]);
    });

    it('reports being closed some other way, such as Escape', async () => {
        dialog(true);

        element().dispatchEvent(new Event('close'));

        expect(wrapper!.emitted('update:open')).toEqual([[false]]);
    });
});
