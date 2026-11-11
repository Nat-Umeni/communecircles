import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ApprovalActions from '@/components/approvals/ApprovalActions.vue';
import PaginatedList from '@/components/ui/pagination/PaginatedList.vue';
import Index from '@/pages/approvals/Index.vue';
import type { Approval, Loaded, Paginated } from '@/types';

type Queued = Loaded<Approval, 'subject' | 'requester'>;
import { paginated, signIn } from '@tests/support';

const exchange: Queued = {
    id: 5,
    type: { value: 'new_circle', label: 'New circle' },
    status: { value: 'pending', label: 'Waiting for approval' },
    reason: null,
    requested_at: '2026-11-08T09:00:00+00:00',
    can: { decide: true, cancel: false },
    subject: { name: 'Seed Exchange', description: 'Swapping seeds that grow well here.', url: '/circles/abc123' },
    requester: { first_name: 'Grace', last_name: 'Hopper' },
};

function queue(data: Queued[], pages: Partial<Paginated<Queued>> = {}): VueWrapper {
    signIn({ role: { value: 'admin', label: 'Admin', internal: true } });

    return mount(Index, {
        props: { approvals: paginated(data, pages) },
    });
}

describe('approvals/Index', () => {
    it('lists what is waiting for approval, each with what kind it is, who asked, since when, and the decision', () => {
        const wrapper = queue([exchange]);
        const card = wrapper.get('[data-approval]');

        expect(card.get('h3 a').text()).toBe('Seed Exchange');
        expect(card.get('h3 a').attributes('href')).toBe('/circles/abc123');
        expect(card.text()).toContain('Swapping seeds that grow well here.');
        expect(card.get('[data-type]').text()).toBe('New circle');
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

    it("doesn't assume what kind of thing is waiting", () => {
        expect(queue([]).get('main').text()).not.toMatch(/circle/i);
    });

    it('pages through the queue in a list of its own, reloading only the queue', () => {
        const wrapper = queue([exchange], { last_page: 2, total: 30, path: '/approvals' });
        const list = wrapper.getComponent(PaginatedList);

        expect(list.props()).toEqual(expect.objectContaining({ label: 'Approvals', only: ['approvals'], size: 'page' }));
        expect(list.get('[role="region"]').find('[data-approval]').exists()).toBe(true);
        expect(wrapper.get('nav[aria-label="Approvals pages"]').find('a[href="/approvals?page=2"]').exists()).toBe(true);
    });
});
