import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Checkbox from '@/components/ui/form/Checkbox.vue';

describe('Checkbox', () => {
    it('is unticked by default and reports ticking through v-model', async () => {
        const wrapper = mount(Checkbox);
        const input = wrapper.element as HTMLInputElement;

        expect(input.type).toBe('checkbox');
        expect(input.checked).toBe(false);

        await wrapper.setValue(true);

        expect(wrapper.emitted('update:modelValue')).toEqual([[true]]);
    });

    it('shows the value it is given', () => {
        const wrapper = mount(Checkbox, { props: { modelValue: true } });

        expect((wrapper.element as HTMLInputElement).checked).toBe(true);
    });
});
