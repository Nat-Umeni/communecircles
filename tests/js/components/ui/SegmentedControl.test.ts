import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { markRaw } from 'vue';
import SegmentedControl from '@/components/ui/SegmentedControl.vue';
import type { Option } from '@/types';

const feeds: Option[] = [
    { value: 'latest', label: 'Latest' },
    { value: 'circles', label: 'Circles' },
];

const Icon = markRaw({ template: '<svg data-icon />' });

describe('SegmentedControl', () => {
    it('shows each option as a button and marks the selected one', () => {
        const wrapper = mount(SegmentedControl, { props: { options: feeds, modelValue: 'circles', label: 'Feed' } });
        const buttons = wrapper.findAll('button');

        expect(wrapper.attributes('role')).toBe('group');
        expect(wrapper.attributes('aria-label')).toBe('Feed');
        expect(buttons.map((button) => button.text())).toEqual(['Latest', 'Circles']);
        expect(buttons[0].attributes('aria-pressed')).toBe('false');
        expect(buttons[1].attributes('aria-pressed')).toBe('true');
        expect(buttons[1].classes()).toContain('bg-primary');
        expect(buttons[0].classes()).toContain('text-muted-foreground');
    });

    it('selects an option when it is clicked', async () => {
        const wrapper = mount(SegmentedControl, { props: { options: feeds, modelValue: 'latest' } });

        await wrapper.findAll('button')[1].trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([['circles']]);
    });

    it('shows only the icon for options that have one, keeping the label for screen readers', () => {
        const wrapper = mount(SegmentedControl, { props: { options: [{ value: 'dark', label: 'Dark theme', icon: Icon }], modelValue: 'dark' } });
        const button = wrapper.get('button');

        expect(button.find('[data-icon]').exists()).toBe(true);
        expect(button.attributes('title')).toBe('Dark theme');
        expect(button.get('span').classes()).toContain('sr-only');
    });

    it('stretches to full width as a block', () => {
        const block = mount(SegmentedControl, { props: { options: feeds, modelValue: 'latest', block: true } });
        const inline = mount(SegmentedControl, { props: { options: feeds, modelValue: 'latest' } });

        expect(block.classes()).toContain('w-full');
        expect(block.get('button').classes()).toContain('flex-1');
        expect(inline.classes()).toContain('inline-flex');
    });

    it('can be pill shaped', () => {
        const wrapper = mount(SegmentedControl, { props: { options: feeds, modelValue: 'latest', shape: 'pill' } });

        expect(wrapper.classes()).toContain('rounded-full');
        expect(wrapper.get('button').classes()).toContain('rounded-full');
    });

    it('uses light-on-green styling on the brand header bar', () => {
        const wrapper = mount(SegmentedControl, { props: { options: feeds, modelValue: 'circles', tone: 'brand' } });
        const buttons = wrapper.findAll('button');

        expect(wrapper.classes()).toContain('bg-brand-foreground/10');
        expect(buttons[1].classes()).toContain('bg-brand-foreground');
        expect(buttons[0].classes()).toContain('text-brand-foreground/80');
    });
});
