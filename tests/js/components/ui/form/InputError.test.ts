import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import InputError from '@/components/ui/form/InputError.vue';

describe('InputError', () => {
    it('shows the message', () => {
        const wrapper = mount(InputError, { props: { message: 'The email field is required.' } });

        expect(wrapper.text()).toBe('The email field is required.');
    });

    it('renders nothing without a message', () => {
        const wrapper = mount(InputError);

        expect(wrapper.find('p').exists()).toBe(false);
    });
});
