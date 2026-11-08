import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import PasswordInput from '@/components/ui/form/PasswordInput.vue';

describe('PasswordInput', () => {
    it('hides the password, and puts attributes on the input rather than the wrapper', () => {
        const wrapper = mount(PasswordInput, { attrs: { id: 'password', name: 'password', autocomplete: 'new-password' } });
        const input = wrapper.get('input');

        expect(input.attributes('type')).toBe('password');
        expect(input.attributes()).toMatchObject({ id: 'password', name: 'password', autocomplete: 'new-password' });
        expect(wrapper.attributes('id')).toBeUndefined();
    });

    it('shows and hides the password with the toggle', async () => {
        const wrapper = mount(PasswordInput);
        const toggle = wrapper.get('button');

        expect(toggle.attributes('aria-label')).toBe('Show password');
        expect(toggle.attributes('aria-pressed')).toBe('false');

        await toggle.trigger('click');

        expect(wrapper.get('input').attributes('type')).toBe('text');
        expect(toggle.attributes('aria-pressed')).toBe('true');

        await toggle.trigger('click');

        expect(wrapper.get('input').attributes('type')).toBe('password');
    });

    it('reports typing through v-model', async () => {
        const wrapper = mount(PasswordInput, { props: { modelValue: '' } });

        await wrapper.get('input').setValue('Analytical1!');

        expect(wrapper.emitted('update:modelValue')).toEqual([['Analytical1!']]);
    });
});
