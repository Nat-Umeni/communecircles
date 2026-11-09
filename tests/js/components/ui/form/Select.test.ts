import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Select from '@/components/ui/form/Select.vue';

const countries = [
    { value: 'GB', label: 'United Kingdom' },
    { value: 'LI', label: 'Liechtenstein' },
];

describe('Select', () => {
    it('lists the options and reports the choice through v-model', async () => {
        const wrapper = mount(Select, { props: { options: countries, modelValue: 'GB' } });

        expect(wrapper.findAll('option').map((option) => [option.attributes('value'), option.text()])).toEqual([
            ['GB', 'United Kingdom'],
            ['LI', 'Liechtenstein'],
        ]);
        expect(wrapper.get<HTMLSelectElement>('select').element.value).toBe('GB');

        await wrapper.get('select').setValue('LI');

        expect(wrapper.emitted('update:modelValue')).toEqual([['LI']]);
    });

    it('starts on a placeholder that cannot be picked again, and passes attributes through', () => {
        const wrapper = mount(Select, { props: { options: countries, placeholder: 'Choose a country' }, attrs: { name: 'country_code' } });
        const placeholder = wrapper.get('option');

        expect(placeholder.text()).toBe('Choose a country');
        expect(placeholder.attributes()).toMatchObject({ value: '', disabled: '' });
        expect(wrapper.get<HTMLSelectElement>('select').element.value).toBe('');
        expect(wrapper.get('select').attributes('name')).toBe('country_code');
    });
});
