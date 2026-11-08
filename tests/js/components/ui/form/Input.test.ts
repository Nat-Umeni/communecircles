import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Input from '@/components/ui/form/Input.vue';

describe('Input', () => {
    it('shows its value and reports typing through v-model', async () => {
        const wrapper = mount(Input, { props: { modelValue: 'ada' } });

        expect((wrapper.element as HTMLInputElement).value).toBe('ada');

        await wrapper.setValue('ada@example.com');

        expect(wrapper.emitted('update:modelValue')).toEqual([['ada@example.com']]);
    });

    it('starts empty and passes attributes through to the input', () => {
        const wrapper = mount(Input, { attrs: { type: 'email', name: 'email', placeholder: 'you@example.com' } });

        expect((wrapper.element as HTMLInputElement).value).toBe('');
        expect(wrapper.attributes()).toMatchObject({ type: 'email', name: 'email', placeholder: 'you@example.com' });
    });
});
