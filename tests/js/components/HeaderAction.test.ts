import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import HeaderAction from '@/components/HeaderAction.vue';

describe('HeaderAction', () => {
    it('links to the action, named by its label, showing its icon', () => {
        const wrapper = mount(HeaderAction, {
            props: { href: '/circles.edit/abc123', label: 'Edit circle' },
            slots: { default: '<svg data-icon />' },
        });
        const link = wrapper.get('a');

        expect(link.attributes('href')).toBe('/circles.edit/abc123');
        expect(link.attributes('aria-label')).toBe('Edit circle');
        expect(link.attributes('title')).toBe('Edit circle');
        expect(link.find('[data-icon]').exists()).toBe(true);
    });
});
