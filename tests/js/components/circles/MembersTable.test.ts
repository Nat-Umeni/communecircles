import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import MembersTable from '@/components/circles/MembersTable.vue';
import type { Loaded, User } from '@/types';

const members: Loaded<User, 'membership'>[] = [
    { first_name: 'Grace', last_name: 'Hopper', membership: { id: 1, role: { value: 'owner', label: 'Owner' }, joined_at: '2026-11-01T09:00:00+00:00' } },
    { first_name: 'Alan', last_name: 'Turing', membership: { id: 2, role: { value: 'member', label: 'Member' }, joined_at: '2026-11-07T09:00:00+00:00' } },
];

describe('MembersTable', () => {
    it('lists each member with their role and when they joined, in the order given', () => {
        const wrapper = mount(MembersTable, { props: { members } });
        const rows = wrapper.findAll('tbody tr');

        expect(wrapper.findAll('thead th').map((heading) => heading.text())).toEqual(['Name', 'Role', 'Joined']);
        expect(rows.map((row) => row.findAll('td').slice(0, 2).map((cell) => cell.text()))).toEqual([
            ['Grace Hopper', 'Owner'],
            ['Alan Turing', 'Member'],
        ]);
        expect(rows.map((row) => row.get('time').attributes('datetime'))).toEqual(['2026-11-01T09:00:00+00:00', '2026-11-07T09:00:00+00:00']);
    });
});
