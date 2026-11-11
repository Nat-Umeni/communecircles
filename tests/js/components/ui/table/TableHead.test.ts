import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import TableHead from '@/components/ui/table/TableHead.vue';

describe('TableHead', () => {
    it('heads a column', () => {
        const wrapper = mount(TableHead, { slots: { default: 'Name' } });

        expect(wrapper.element.tagName).toBe('TH');
        expect(wrapper.attributes('scope')).toBe('col');
        expect(wrapper.text()).toBe('Name');
    });
});
