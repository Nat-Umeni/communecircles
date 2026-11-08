import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Tag from '@/components/ui/Tag.vue';

describe('Tag', () => {
    it('shows a channel name after a decorative #', () => {
        const wrapper = mount(Tag, { slots: { default: 'seed-saving' } });

        expect(wrapper.element.tagName).toBe('SPAN');
        expect(wrapper.text()).toBe('#seed-saving');
        expect(wrapper.get('[aria-hidden="true"]').text()).toBe('#');
    });

    it('becomes a link with hover styling when given an href', () => {
        const wrapper = mount(Tag, { props: { href: '/channels/seed-saving' }, slots: { default: 'seed-saving' } });

        expect(wrapper.element.tagName).toBe('A');
        expect(wrapper.attributes('href')).toBe('/channels/seed-saving');
        expect(wrapper.classes()).toContain('hover:bg-highlight');
    });
});
