import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Alert from '@/components/ui/Alert.vue';

describe('Alert', () => {
    it('announces its message as a status, styled as success by default', () => {
        const wrapper = mount(Alert, { slots: { default: 'Your password has been reset.' } });

        expect(wrapper.attributes('role')).toBe('status');
        expect(wrapper.text()).toBe('Your password has been reset.');
        expect(wrapper.classes()).toContain('bg-success/10');
    });

    it('uses the warning styling for the warning variant', () => {
        const wrapper = mount(Alert, { props: { variant: 'warning' }, slots: { default: 'Please confirm your email address.' } });

        expect(wrapper.classes()).toContain('bg-warning/10');
        expect(wrapper.classes()).not.toContain('bg-success/10');
    });
});
