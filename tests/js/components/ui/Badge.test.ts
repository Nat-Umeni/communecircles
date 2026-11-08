import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Badge from '@/components/ui/Badge.vue';

describe('Badge', () => {
    it('shows its text with the primary styling by default', () => {
        const wrapper = mount(Badge, { slots: { default: 'Soon' } });

        expect(wrapper.text()).toBe('Soon');
        expect(wrapper.classes()).toContain('bg-primary');
    });

    it.each([
        ['secondary', 'bg-secondary'],
        ['accent', 'bg-accent'],
        ['success', 'bg-success'],
        ['warning', 'bg-warning'],
        ['danger', 'bg-danger'],
    ] as const)('uses the %s styling', (variant, background) => {
        const wrapper = mount(Badge, { props: { variant }, slots: { default: 'Label' } });

        expect(wrapper.classes()).toContain(background);
    });
});
