import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Label from '@/components/ui/form/Label.vue';

describe('Label', () => {
    it('labels the control it points at', () => {
        const wrapper = mount(Label, { attrs: { for: 'email' }, slots: { default: 'Email address' } });

        expect(wrapper.element.tagName).toBe('LABEL');
        expect(wrapper.attributes('for')).toBe('email');
        expect(wrapper.text()).toBe('Email address');
    });
});
