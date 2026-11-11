import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Table from '@/components/ui/table/Table.vue';

function table() {
    return mount(Table, { slots: { head: '<th>Name</th>', default: '<tr><td>Ada Lovelace</td></tr>' } });
}

describe('Table', () => {
    it('puts the header cells in a header row above the rows', () => {
        const wrapper = table();

        expect(wrapper.get('thead tr th').text()).toBe('Name');
        expect(wrapper.get('tbody tr td').text()).toBe('Ada Lovelace');
    });

    it('keeps its header in view as its list scrolls, above a row grown on hover', () => {
        expect(table().get('thead').classes()).toEqual(expect.arrayContaining(['sticky', 'top-0', 'z-10', 'bg-surface']));
    });

    it('leaves room around itself, so a row grown on hover stays inside a scrolling list', () => {
        expect(table().classes()).toEqual(expect.arrayContaining(['px-2', 'py-1']));
    });
});
