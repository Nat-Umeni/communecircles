import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import TableCell from '@/components/ui/table/TableCell.vue';

describe('TableCell', () => {
    it('holds its content, with room either side for the row tint', () => {
        const wrapper = mount(TableCell, { slots: { default: 'Ada Lovelace' } });

        expect(wrapper.element.tagName).toBe('TD');
        expect(wrapper.text()).toBe('Ada Lovelace');
        expect(wrapper.classes()).toContain('px-3');
    });
});
