import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ApprovalActions from '@/components/approvals/ApprovalActions.vue';
import Index from '@/pages/approvals/Index.vue';
import type { NewCircleApproval, Paginated } from '@/types';
import { signIn } from '@tests/support';

const exchange: NewCircleApproval = {
    id: 5,
    requested_at: '2026-11-08T09:00:00+00:00',
    circle: { public_id: 'abc123', name: 'Seed Exchange', description: 'Swapping seeds that grow well here.' },
    requester: { first_name: 'Grace', last_name: 'Hopper' },
};

function queue(data: NewCircleApproval[], pages: Partial<Paginated<NewCircleApproval>> = {}): VueWrapper {
    signIn({ role: { value: 'admin', label: 'Admin' } });

    return mount(Index, {
        props: { approvals: { data, current_page: 1, last_page: 1, total: data.length, prev_page_url: null, next_page_url: null, ...pages } },
    });
}

describe('approvals/Index', () => {
    it('lists the new circles waiting for approval, each with who asked, since when, and the decision', () => {
        const wrapper = queue([exchange]);
        const card = wrapper.get('[data-approval]');

        expect(card.get('h3 a').text()).toBe('Seed Exchange');
        expect(card.get('h3 a').attributes('href')).toBe('/circles.show/abc123');
        expect(card.text()).toContain('Swapping seeds that grow well here.');
        expect(card.text()).toContain('Asked by Grace Hopper');
        expect(card.get('time').attributes('datetime')).toBe('2026-11-08T09:00:00+00:00');
        expect(wrapper.getComponent(ApprovalActions).props()).toEqual({ approval: 5, subject: 'Seed Exchange', approveLabel: 'Approve', rejectLabel: 'Reject' });
        expect(wrapper.text()).not.toContain('Nothing is waiting');
    });

    it("says when the person who asked doesn't have an account any more", () => {
        expect(queue([{ ...exchange, requester: null }]).get('[data-approval]').text()).toContain('Asked by a deleted account');
    });

    it('says so when nothing is waiting', () => {
        expect(queue([]).text()).toContain('Nothing is waiting for approval');
    });

    it('pages through the queue', () => {
        const wrapper = queue([exchange], { last_page: 2, total: 30, next_page_url: '/approvals?page=2' });

        expect(wrapper.get('nav[aria-label="Pages"]').find('a').attributes('href')).toBe('/approvals?page=2');
    });
});
