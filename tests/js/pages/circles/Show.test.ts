import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ApprovalActions from '@/components/approvals/ApprovalActions.vue';
import DeleteCircle from '@/components/circles/DeleteCircle.vue';
import InviteForm from '@/components/circles/InviteForm.vue';
import Show from '@/pages/circles/Show.vue';
import type { CircleApproval, CirclePermissions, CircleSummary, Invite, JoinRequest, Place, WaitingInvite, WaitingJoinRequest } from '@/types';
import { fakeRouter, links, signIn, visit } from '@tests/support';

const totnes: Place = { geonames_id: 2635610, name: 'Totnes', region: 'England', country_code: 'GB', country_name: 'United Kingdom', latitude: 50.43107, longitude: -3.6843 };

const exchange: CircleSummary = {
    public_id: 'abc123',
    name: 'Seed Exchange',
    description: 'Swapping seeds that grow well here.',
    members_count: 3,
    location: { name: null, place: totnes, country_code: 'GB', country_name: 'United Kingdom' },
};

const nothing: CirclePermissions = { view_channels: false, request_to_join: false, invite: false, update: false, delete: false };

const waiting: CircleApproval = { id: 5, status: 'pending', label: 'Waiting for approval', reason: null, can_decide: false };

interface ShowProps {
    circle: CircleSummary;
    can: CirclePermissions;
    approval: CircleApproval | null;
    join_request: JoinRequest | null;
    pending_join_requests: WaitingJoinRequest[] | null;
    invite: Invite | null;
    pending_invites: WaitingInvite[] | null;
}

/**
 * The circle's page as CircleController::show sends it; by default to a viewer who can do nothing but look.
 */
function show(props: Partial<ShowProps> = {}): VueWrapper {
    return mount(Show, {
        props: { circle: exchange, can: nothing, approval: null, join_request: null, pending_join_requests: null, invite: null, pending_invites: null, ...props },
    });
}

function decisions(wrapper: VueWrapper): unknown[] {
    return wrapper.findAllComponents(ApprovalActions).map((actions) => actions.props());
}

function button(wrapper: VueWrapper, label: string) {
    return wrapper.findAll('button').find((found) => found.text() === label);
}

describe('circles/Show', () => {
    it('shows the circle, where it is and how many are in it', () => {
        const text = show().text();

        expect(text).toContain('Seed Exchange');
        expect(text).toContain('Swapping seeds that grow well here.');
        expect(text).toContain('Totnes, England, United Kingdom');
        expect(text).toContain('3 members');
        expect(show({ circle: { ...exchange, members_count: 1, location: null } }).text()).toContain('1 member');
    });

    it('lets those who can edit the circle', () => {
        signIn();

        expect(links(show({ can: { ...nothing, update: true } }))).toContainEqual(['Edit', '/circles.edit/abc123']);
    });

    it('lets those who can delete the circle', () => {
        signIn();

        expect(show({ can: { ...nothing, delete: true } }).getComponent(DeleteCircle).props('circle')).toEqual(exchange);
    });

    it('lets those who can ask to join', async () => {
        signIn();
        const wrapper = show({ can: { ...nothing, request_to_join: true } });

        await button(wrapper, 'Ask to join')!.trigger('click');

        expect(fakeRouter.post).toHaveBeenCalledWith('/circles.join-requests.store/abc123', {}, expect.objectContaining({ preserveScroll: true }));

        await visit.start(wrapper);

        expect(button(wrapper, 'Ask to join')!.attributes('disabled')).toBeDefined();
    });

    it('invites guests to log in to ask to join', () => {
        expect(links(show())).toContainEqual(['Log in to ask to join', '/login']);
    });

    it("says where the member's own request to join stands", () => {
        signIn();

        expect(show({ join_request: { status: 'pending', label: 'Waiting for approval', reason: null } }).get('[data-join-request]').text())
            .toContain('Your request to join: Waiting for approval');

        const turnedDown = show({ join_request: { status: 'rejected', label: 'Not approved', reason: 'We only take members from Devon.' } }).get('[data-join-request]').text();
        expect(turnedDown).toContain('Your request to join: Not approved');
        expect(turnedDown).toContain('“We only take members from Devon.”');
    });

    it('says the circle is waiting for approval', () => {
        signIn();

        const text = show({ approval: waiting }).get('[data-approval]').text();

        expect(text).toContain('Waiting for approval');
        expect(text).toContain("The team checks each new circle before it's listed. Until then, only its members and the team can see it.");
    });

    it("says why the circle wasn't approved, and how to send it again", () => {
        signIn();
        const wrapper = show({
            approval: { ...waiting, status: 'rejected', label: 'Not approved', reason: 'There is already a seed circle in Totnes.' },
            can: { ...nothing, update: true },
        });

        const text = wrapper.get('[data-approval]').text();

        expect(text).toContain('Not approved');
        expect(text).toContain('“There is already a seed circle in Totnes.”');
        expect(text).toContain('Edit the circle to send it to the team again.');
        expect(text).not.toContain('The team checks each new circle');
    });

    it('lets staff approve or reject the circle', () => {
        signIn();

        expect(decisions(show({ approval: { ...waiting, can_decide: true } }))).toEqual([
            { approval: 5, subject: 'Seed Exchange', approveLabel: 'Approve', rejectLabel: 'Reject' },
        ]);
    });

    it('lets someone who was invited accept or decline', () => {
        signIn();
        const wrapper = show({ invite: { id: 9, invited_by: 'Ada Lovelace' } });

        expect(wrapper.text()).toContain('Ada Lovelace invited you to join this circle.');
        expect(decisions(wrapper)).toEqual([{ approval: 9, subject: 'the invite', approveLabel: 'Accept', rejectLabel: 'Decline' }]);
        expect(show({ invite: { id: 9, invited_by: null } }).text()).toContain("You've been invited to join this circle.");
    });

    it("lists the requests to join for the circle's owners and admins to decide", () => {
        signIn();
        const wrapper = show({ pending_join_requests: [{ id: 3, name: 'Grace Hopper', requested_at: '2026-11-08T09:00:00+00:00' }] });
        const request = wrapper.get('[data-join-requests] li');

        expect(request.text()).toContain('Grace Hopper');
        expect(request.get('time').attributes('datetime')).toBe('2026-11-08T09:00:00+00:00');
        expect(decisions(wrapper)).toEqual([{ approval: 3, subject: "Grace Hopper's request", approveLabel: 'Accept', rejectLabel: 'Decline' }]);
        expect(show({ pending_join_requests: [] }).get('[data-join-requests]').text()).toContain('No one is waiting to join.');
    });

    it("lets the circle's owners and admins invite people, and lists who hasn't answered", () => {
        signIn();
        const wrapper = show({
            can: { ...nothing, invite: true },
            pending_invites: [{ id: 4, name: 'Alan Turing', invited_at: '2026-11-07T09:00:00+00:00' }],
        });
        const invited = wrapper.get('[data-invites] li');

        expect(wrapper.getComponent(InviteForm).props('circle')).toBe('abc123');
        expect(invited.text()).toContain('Alan Turing');
        expect(invited.get('time').attributes('datetime')).toBe('2026-11-07T09:00:00+00:00');
    });

    it("doesn't offer anything more to a member who can only look", () => {
        signIn();
        const wrapper = show();

        expect(button(wrapper, 'Ask to join')).toBeUndefined();
        expect(links(wrapper).map(([label]) => label)).not.toContain('Edit');
        expect(links(wrapper).map(([label]) => label)).not.toContain('Log in to ask to join');
        expect(wrapper.findComponent(DeleteCircle).exists()).toBe(false);
        expect(decisions(wrapper)).toEqual([]);
        expect(wrapper.findComponent(InviteForm).exists()).toBe(false);
        expect(wrapper.find('[data-approval]').exists()).toBe(false);
        expect(wrapper.find('[data-join-request]').exists()).toBe(false);
        expect(wrapper.find('[data-join-requests]').exists()).toBe(false);
        expect(wrapper.find('[data-invites]').exists()).toBe(false);
        expect(wrapper.find('[data-channels]').exists()).toBe(false);
    });

    it("doesn't let anyone but staff decide on the circle", () => {
        signIn();

        expect(decisions(show({ approval: waiting }))).toEqual([]);
    });

    it("doesn't suggest editing a circle that wasn't approved to those who can't edit it", () => {
        signIn();
        const wrapper = show({ approval: { ...waiting, status: 'rejected', label: 'Not approved' } });

        expect(wrapper.get('[data-approval]').text()).not.toContain('Edit the circle');
    });

    it("doesn't offer to invite people until the circle can take them", () => {
        signIn();

        expect(show({ approval: waiting, pending_invites: [] }).find('[data-invites]').exists()).toBe(false);
    });

    it('shows members where the channels will be', () => {
        signIn();

        expect(show({ can: { ...nothing, view_channels: true } }).get('[data-channels]').text()).toContain('Channels');
    });
});
