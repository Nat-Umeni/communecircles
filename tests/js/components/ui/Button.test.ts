import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Button from '@/components/ui/Button.vue';
import { fakeRouter } from '@tests/support';

describe('Button', () => {
    it('is a plain button by default, in the primary medium style', () => {
        const wrapper = mount(Button, { slots: { default: 'Save' } });

        expect(wrapper.element.tagName).toBe('BUTTON');
        expect(wrapper.attributes('type')).toBe('button');
        expect(wrapper.text()).toBe('Save');
        expect(wrapper.classes()).toEqual(expect.arrayContaining(['bg-primary', 'h-11']));
    });

    it('can submit a form', () => {
        const wrapper = mount(Button, { props: { type: 'submit' }, slots: { default: 'Log in' } });

        expect(wrapper.attributes('type')).toBe('submit');
    });

    it('becomes an Inertia link when given an href', async () => {
        const wrapper = mount(Button, { props: { href: '/register' }, slots: { default: 'Join the Circle' } });

        expect(wrapper.element.tagName).toBe('A');
        expect(wrapper.attributes('href')).toBe('/register');

        await wrapper.trigger('click');

        expect(fakeRouter.visit).toHaveBeenCalledWith('/register', expect.objectContaining({ method: 'get' }));
    });

    it.each([
        ['secondary', 'bg-secondary'],
        ['accent', 'bg-accent'],
        ['surface', 'bg-surface'],
        ['ghost', 'text-heading'],
    ] as const)('uses the %s style', (variant, styleClass) => {
        const wrapper = mount(Button, { props: { variant }, slots: { default: 'Go' } });

        expect(wrapper.classes()).toContain(styleClass);
    });

    it.each([
        ['sm', 'h-9'],
        ['lg', 'h-14'],
    ] as const)('uses the %s size', (size, heightClass) => {
        const wrapper = mount(Button, { props: { size }, slots: { default: 'Go' } });

        expect(wrapper.classes()).toContain(heightClass);
    });
});
