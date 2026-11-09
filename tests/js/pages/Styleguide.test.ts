import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Styleguide from '@/pages/Styleguide.vue';
import { groupButtons } from '@tests/support';

const props = {
    tokensPath: 'resources/theme/tokens.json',
    themeColours: [
        { name: 'primary', foreground: 'primary-foreground', light: { name: 'forest-900', hex: '#184D3B' }, dark: { name: 'sage-500', hex: '#A8D5A2' } },
        { name: 'border', foreground: null, light: { name: 'stone-200', hex: '#E1E6DD' }, dark: { name: 'night-700', hex: '#263A31' } },
    ],
    palette: [
        {
            group: 'forest',
            shades: [
                { name: 'forest-900', hex: '#184D3B', description: 'Brand green' },
                { name: 'forest-500', hex: '#689372', description: null },
            ],
        },
    ],
    fonts: [{ name: 'sans', family: 'Nunito Sans', description: 'Body text' }],
    icons: ['circles', 'explore'],
};

function styleguide() {
    return mount(Styleguide, { props });
}

describe('Styleguide', () => {
    it('shows where the colours come from and every theme colour, with a sample only on colours that have text colours', () => {
        const wrapper = styleguide();

        expect(wrapper.text()).toContain('resources/theme/tokens.json');
        expect(wrapper.text()).toContain('light forest-900 · #184D3B');
        expect(wrapper.text()).toContain('dark night-700 · #263A31');
        expect(wrapper.findAll('span').filter((span) => span.text() === 'Aa')).toHaveLength(1);
    });

    it('shows the palette, with descriptions as tooltips where there are any', () => {
        const wrapper = styleguide();
        const shades = wrapper.findAll('div.w-28');

        expect(shades.map((shade) => shade.attributes('title'))).toEqual(['Brand green', undefined]);
        expect(wrapper.text()).toContain('forest-500');
    });

    it('lists the fonts and every brand icon', () => {
        const wrapper = styleguide();

        expect(wrapper.text()).toContain('font-sans: Nunito Sans — Body text');
        expect(wrapper.findAll('img[src="/images/icons/explore.png"]').length).toBeGreaterThan(0);
    });

    it('keeps the form control demos working', async () => {
        const wrapper = styleguide();
        const example = wrapper.get<HTMLInputElement>('input[aria-label="Example input"]');
        const hintId = wrapper.findAll('label').find((label) => label.text() === 'Field with a hint')!.attributes('for');
        const withHint = wrapper.get<HTMLInputElement>(`[id="${hintId}"]`);

        await example.setValue('typed');
        expect(withHint.element.value).toBe('typed');

        await withHint.setValue('typed again');
        expect(example.element.value).toBe('typed again');

        const password = wrapper.get<HTMLInputElement>('input[type="password"]');
        await password.setValue('Analytical1!');
        expect(password.element.value).toBe('Analytical1!');

        const checkbox = wrapper.get<HTMLInputElement>('input[type="checkbox"]');
        await checkbox.setValue(true);
        expect(checkbox.element.checked).toBe(true);
    });

    it('demos the textarea and the select', async () => {
        const wrapper = styleguide();
        const textarea = wrapper.get<HTMLTextAreaElement>('textarea[aria-label="Example textarea"]');
        const select = wrapper.get<HTMLSelectElement>('select[aria-label="Example select"]');

        await textarea.setValue('A longer answer.');
        await select.setValue('LI');

        expect(textarea.element.value).toBe('A longer answer.');
        expect(select.element.value).toBe('LI');
    });

    it('demos paging', () => {
        const pages = styleguide().get('nav[aria-label="Pages"]');

        expect(pages.text()).toContain('Page 2 of 3');
    });

    it('demos a dialog that opens from a button and closes again', async () => {
        const wrapper = mount(Styleguide, { props, attachTo: document.body });
        const dialog = () => wrapper.get<HTMLDialogElement>('dialog').element;

        expect(dialog().open).toBe(false);

        await wrapper.get('button[data-demo="open-dialog"]').trigger('click');
        expect(dialog().open).toBe(true);
        expect(wrapper.get('dialog h2').text()).toBe('Example dialog');

        await wrapper.get('dialog button[aria-label="Close"]').trigger('click');
        expect(dialog().open).toBe(false);
        wrapper.unmount();
    });

    it('closes the demo dialog with either of its actions', async () => {
        const wrapper = mount(Styleguide, { props, attachTo: document.body });
        const dialog = () => wrapper.get<HTMLDialogElement>('dialog').element;
        const action = (label: string) => wrapper.findAll('dialog footer button').find((button) => button.text() === label)!;

        for (const label of ['Cancel', 'Confirm']) {
            await wrapper.get('button[data-demo="open-dialog"]').trigger('click');
            await action(label).trigger('click');
            expect(dialog().open, label).toBe(false);
        }

        wrapper.unmount();
    });

    it('demos the dropdown menu', async () => {
        const wrapper = mount(Styleguide, { props, attachTo: document.body });

        await wrapper.get('[aria-label="Example menu"]').trigger('click');

        expect(wrapper.findAll('[role="menuitem"]').map((item) => item.text())).toEqual(['Edit', 'Log out']);
        wrapper.unmount();
    });

    it('demos the menu in the header bar\'s brand tone', async () => {
        const wrapper = mount(Styleguide, { props, attachTo: document.body });

        await wrapper.get('[aria-label="Example brand menu"]').trigger('click');

        expect(wrapper.findAll('[role="menuitem"]').map((item) => item.text())).toEqual(['Create a circle']);
        wrapper.unmount();
    });

    it('keeps the segmented control demos working', async () => {
        const wrapper = styleguide();
        const tabs = () => groupButtons(wrapper, 'Example tabs');
        const pill = () => groupButtons(wrapper, 'Example pill switch');

        await tabs()[0].trigger('click');
        expect(tabs()[0].attributes('aria-pressed')).toBe('true');

        await pill()[1].trigger('click');
        expect(pill()[1].attributes('aria-pressed')).toBe('true');
    });
});
