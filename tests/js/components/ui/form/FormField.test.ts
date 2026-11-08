import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import FormField from '@/components/ui/form/FormField.vue';
import Input from '@/components/ui/form/Input.vue';

function field(props: { label: string; hint?: string; error?: string }) {
    return mount({
        components: { FormField, Input },
        setup: () => ({ props }),
        template: '<FormField v-bind="props" v-slot="control"><Input v-bind="control" /></FormField>',
    });
}

describe('FormField', () => {
    it('labels the control', () => {
        const wrapper = field({ label: 'Email address' });
        const input = wrapper.get('input');

        expect(wrapper.get('label').text()).toBe('Email address');
        expect(wrapper.get('label').attributes('for')).toBe(input.attributes('id'));
        expect(input.attributes('aria-describedby')).toBeUndefined();
        expect(input.attributes('aria-invalid')).toBeUndefined();
    });

    it('shows a hint and links it to the control', () => {
        const wrapper = field({ label: 'Password', hint: 'At least 8 characters.' });
        const hint = wrapper.get('p');

        expect(hint.text()).toBe('At least 8 characters.');
        expect(wrapper.get('input').attributes('aria-describedby')).toBe(hint.attributes('id'));
    });

    it('marks the control invalid and links the error to it', () => {
        const wrapper = field({ label: 'Email address', error: 'The email field is required.' });
        const input = wrapper.get('input');
        const error = wrapper.get('p.text-danger');

        expect(error.text()).toBe('The email field is required.');
        expect(input.attributes('aria-invalid')).toBe('true');
        expect(input.attributes('aria-describedby')).toBe(error.attributes('id'));
    });

    it('links both the hint and the error when there are both', () => {
        const wrapper = field({ label: 'Password', hint: 'At least 8 characters.', error: 'Too short.' });
        const [hint, error] = wrapper.findAll('p');

        expect(wrapper.get('input').attributes('aria-describedby')).toBe(`${hint.attributes('id')} ${error.attributes('id')}`);
    });
});
