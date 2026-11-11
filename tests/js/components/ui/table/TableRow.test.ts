import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import TableRow from '@/components/ui/table/TableRow.vue';

function row() {
    return mount(TableRow, { slots: { default: '<td>Ada Lovelace</td>' } });
}

describe('TableRow', () => {
    it('holds its cells', () => {
        expect(row().get('td').text()).toBe('Ada Lovelace');
    });

    it('tints a little deeper and grows slightly on hover, growing only for those who are fine with motion', () => {
        const classes = row().classes();

        expect(classes).toEqual(expect.arrayContaining(['transition', 'hover:bg-secondary/60', 'motion-safe:hover:scale-101']));
        expect(classes).not.toContain('hover:scale-101');
    });
});
