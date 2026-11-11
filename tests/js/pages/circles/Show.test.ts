import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ApprovalActions from '@/components/approvals/ApprovalActions.vue';
import CancelInvite from '@/components/circles/CancelInvite.vue';
import DeleteCircle from '@/components/circles/DeleteCircle.vue';
import InviteDialog from '@/components/circles/InviteDialog.vue';
import MembersTable from '@/components/circles/MembersTable.vue';
import CountPill from '@/components/ui/CountPill.vue';
import PaginatedList from '@/components/ui/pagination/PaginatedList.vue';
import SearchBar from '@/components/ui/search/SearchBar.vue';
import Show from '@/pages/circles/Show.vue';
import type { Approval, Circle, CirclePermissions, Loaded, Paginated, Place, User } from '@/types';
import { fakeRouter, links, paginated, signIn, visit } from '@tests/support';

const totnes: Place = { geonames_id: 2635610, name: 'Totnes', region: 'England', country_code: 'GB', country_name: 'United Kingdom', latitude: 50.43107, longitude: -3.6843 };

const exchange: Loaded<Circle, 'members_count'> = {
    public_id: 'abc123',
    name: 'Seed Exchange',
    description: 'Swapping seeds that grow well here.',
    members_count: 3,
    location: { name: null, place: totnes, country_code: 'GB', country_name: 'United Kingdom' },
};

const nothing: CirclePermissions = { view_channels: false, request_to_join: false, invite: false, update: false, delete: false };

/**
 * An approval as ApprovalResource sends it; by default a new circle waiting for approval, which the viewer can't act on.
 */
function approval(overrides: Partial<Approval> = {}): Approval {
    return {
        id: 5,
        type: { value: 'new_circle', label: 'New circle' },
        status: { value: 'pending', label: 'Waiting for approval' },
        reason: null,
        requested_at: '2026-11-08T09:00:00+00:00',
        can: { decide: false, cancel: false },
        ...overrides,
    };
}

const waiting = approval();

const grace: User = { first_name: 'Grace', last_name: 'Hopper' };

const alan: User = { first_name: 'Alan', last_name: 'Turing' };

interface ShowProps {
    circle: Loaded<Circle, 'members_count'>;
    can: CirclePermissions;
    approval: Approval | null;
    join_request: Approval | null;
    pending_join_requests: Paginated<Loaded<Approval, 'user'>> | null;
    invite: Loaded<Approval, 'requester'> | null;
    pending_invites: Paginated<Loaded<Approval, 'user'>> | null;
    members: Paginated<Loaded<User, 'membership'>> | null;
    filters: { members_search: string };
}

/**
 * The circle's page as CircleController::show sends it; by default to a viewer who can do nothing but look.
 */
function show(props: Partial<ShowProps> = {}, attachTo?: HTMLElement): VueWrapper {
    return mount(Show, {
        props: { circle: exchange, can: nothing, approval: null, join_request: null, pending_join_requests: null, invite: null, pending_invites: null, members: null, filters: { members_search: '' }, ...props },
        attachTo,
    });
}

/**
 * The items in the header's Create menu, opened.
 */
async function createMenu(wrapper: VueWrapper): Promise<string[]> {
    await wrapper.get('[aria-label="Create"]').trigger('click');

    return wrapper.findAll('[role="menuitem"]').map((item) => item.text());
}

function decisions(wrapper: VueWrapper): unknown[] {
    return wrapper.findAllComponents(ApprovalActions).map((actions) => actions.props());
}

function button(wrapper: VueWrapper, label: string) {
    return wrapper.findAll('button').find((found) => found.text() === label);
}

describe('circles/Show', () => {
    it('shows the circle, where it is and how many are in it', () => {
        const wrapper = show();

        expect(wrapper.text()).toContain('Seed Exchange');
        expect(wrapper.text()).toContain('Swapping seeds that grow well here.');
        expect(wrapper.text()).toContain('Totnes, England, United Kingdom');
        expect(wrapper.getComponent(CountPill).props()).toEqual({ count: 3, singular: 'member', plural: 'members' });
        expect(show({ circle: { ...exchange, location: null } }).text()).not.toContain('Totnes');
    });

    it('lets those who can edit the circle from the header', () => {
        signIn();
        const wrapper = show({ can: { ...nothing, update: true } });

        expect(wrapper.get('header a[aria-label="Edit circle"]').attributes('href')).toBe('/circles.edit/abc123');
        expect(wrapper.get('main').find('a[href="/circles.edit/abc123"]').exists()).toBe(false);
    });

    it('shows everyone in the circle to those who can see its members', () => {
        signIn();
        const members = paginated([{ ...grace, membership: { id: 7, role: { value: 'owner' as const, label: 'Owner' }, joined_at: '2026-11-01T09:00:00+00:00' as const } }]);
        const card = show({ members }).get('[data-members]');

        expect(card.getComponent(MembersTable).props('members')).toEqual(members.data);
        expect(card.getComponent(PaginatedList).props()).toEqual(expect.objectContaining({ paginator: members, label: 'Members', only: ['members'], size: 'card' }));
    });

    it("searches the members from the card's title row, bringing the search back into view for a new page", async () => {
        signIn();
        const members = paginated([{ ...grace, membership: { id: 7, role: { value: 'owner' as const, label: 'Owner' }, joined_at: '2026-11-01T09:00:00+00:00' as const } }]);
        const wrapper = show({ members, filters: { members_search: 'gra' } });
        const card = wrapper.get('[data-members]');

        expect(card.get('header').getComponent(SearchBar).props()).toEqual({
            param: 'members_search',
            pageName: 'members_page',
            search: 'gra',
            only: ['members', 'filters'],
            label: 'Search members',
            placeholder: 'Search members by name',
        });
        // The search bar is a template ref, filled in once the page has rendered.
        await wrapper.vm.$nextTick();
        expect(card.getComponent(PaginatedList).props('anchor')).toBe(card.get('form[role="search"]').element);
        expect(card.get('form[role="search"]').classes()).toContain('scroll-mt-24');
    });

    it('says so when no member matches the search', () => {
        signIn();
        const card = show({ members: paginated([]), filters: { members_search: 'zz' } }).get('[data-members]');

        expect(card.text()).toContain('No members match “zz”');
        expect(card.findComponent(MembersTable).exists()).toBe(false);
        expect(card.find('form[role="search"]').exists()).toBe(true);
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

        const asked = approval({ type: { value: 'join_request', label: 'Request to join' } });

        expect(show({ join_request: asked }).get('[data-join-request]').text()).toContain('Your request to join: Waiting for approval');

        const turnedDown = show({
            join_request: { ...asked, status: { value: 'rejected', label: 'Not approved' }, reason: 'We only take members from Devon.' },
        }).get('[data-join-request]').text();
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
            approval: { ...waiting, status: { value: 'rejected', label: 'Not approved' }, reason: 'There is already a seed circle in Totnes.' },
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

        expect(decisions(show({ approval: { ...waiting, can: { decide: true, cancel: false } } }))).toEqual([
            { approval: 5, subject: 'Seed Exchange', approveLabel: 'Approve', rejectLabel: 'Reject' },
        ]);
    });

    it('lets someone who was invited accept or decline', () => {
        signIn();
        const invite = { ...approval({ id: 9, type: { value: 'invite', label: 'Invite' }, can: { decide: true, cancel: false } }), requester: grace };
        const wrapper = show({ invite });

        expect(wrapper.text()).toContain('Grace Hopper invited you to join this circle.');
        expect(decisions(wrapper)).toEqual([{ approval: 9, subject: 'the invite', approveLabel: 'Accept', rejectLabel: 'Decline' }]);
        expect(show({ invite: { ...invite, requester: null } }).text()).toContain("You've been invited to join this circle.");
    });

    it('tells someone whose invite was withdrawn only that it expired', () => {
        signIn();
        const wrapper = show({
            invite: { ...approval({ id: 9, type: { value: 'invite', label: 'Invite' }, status: { value: 'expired', label: 'Expired' } }), requester: grace },
        });

        expect(wrapper.text()).toContain('This invite has expired');
        expect(wrapper.text()).not.toContain('invited you to join');
        expect(wrapper.text()).not.toMatch(/cancel/i);
        expect(decisions(wrapper)).toEqual([]);
    });

    it("lists the requests to join for the circle's owners and admins to decide", () => {
        signIn();
        const wrapper = show({
            pending_join_requests: paginated([{ ...approval({ id: 3, type: { value: 'join_request', label: 'Request to join' }, can: { decide: true, cancel: false } }), user: grace }]),
        });
        const request = wrapper.get('[data-join-requests] li');

        expect(request.text()).toContain('Grace Hopper');
        expect(request.get('time').attributes('datetime')).toBe('2026-11-08T09:00:00+00:00');
        expect(decisions(wrapper)).toEqual([{ approval: 3, subject: "Grace Hopper's request", approveLabel: 'Accept', rejectLabel: 'Decline' }]);
        expect(wrapper.get('[data-join-requests]').getComponent(PaginatedList).props()).toEqual(
            expect.objectContaining({ label: 'Requests to join', only: ['pending_join_requests'], size: 'card' }),
        );
        expect(show({ pending_join_requests: paginated([]) }).get('[data-join-requests]').text()).toContain('No one is waiting to join.');
    });

    it("lets the circle's owners and admins invite someone from the header's Create menu", async () => {
        signIn();
        const wrapper = show({ can: { ...nothing, invite: true }, pending_invites: paginated([]) }, document.body);
        const dialog = () => wrapper.getComponent(InviteDialog);

        expect(dialog().props()).toEqual({ circle: 'abc123', open: false });
        expect(await createMenu(wrapper)).toEqual(['Create a circle', 'Invite someone']);
        expect(wrapper.findAll('[role="menuitem"]')[1].find('svg').exists()).toBe(true);

        await wrapper.findAll('[role="menuitem"]')[1].trigger('click');

        expect(dialog().props('open')).toBe(true);
        wrapper.unmount();
    });

    it('opens the invite dialog again from the menu after it closed itself', async () => {
        signIn();
        const wrapper = show({ can: { ...nothing, invite: true }, pending_invites: paginated([]) }, document.body);
        const dialog = () => wrapper.getComponent(InviteDialog);

        await createMenu(wrapper);
        await wrapper.findAll('[role="menuitem"]')[1].trigger('click');
        dialog().vm.$emit('update:open', false);
        await wrapper.vm.$nextTick();

        expect(dialog().props('open')).toBe(false);

        await createMenu(wrapper);
        await wrapper.findAll('[role="menuitem"]')[1].trigger('click');

        expect(dialog().props('open')).toBe(true);
        wrapper.unmount();
    });

    it("lists the invites waiting for an answer, for the circle's owners and admins to cancel", () => {
        signIn();
        const wrapper = show({
            pending_invites: paginated([
                { ...approval({ id: 4, type: { value: 'invite', label: 'Invite' }, requested_at: '2026-11-07T09:00:00+00:00', can: { decide: false, cancel: true } }), user: alan },
            ]),
        });
        const invited = wrapper.get('[data-invites] li');

        expect(wrapper.get('[data-invites] h3').text()).toBe('Invites');
        expect(invited.text()).toContain('Alan Turing');
        expect(invited.get('time').attributes('datetime')).toBe('2026-11-07T09:00:00+00:00');
        expect(invited.getComponent(CancelInvite).props()).toEqual({ invite: 4, name: 'Alan Turing' });
        expect(wrapper.get('[data-invites]').getComponent(PaginatedList).props()).toEqual(expect.objectContaining({ label: 'Invites', only: ['pending_invites'], size: 'card' }));
        expect(show({ pending_invites: paginated([]) }).get('[data-invites]').text()).toContain('No invites are waiting for an answer.');
    });

    it('shows staff the requests and invites waiting, without letting them decide or cancel', () => {
        signIn();
        const wrapper = show({
            pending_join_requests: paginated([{ ...approval({ id: 3, type: { value: 'join_request', label: 'Request to join' } }), user: grace }]),
            pending_invites: paginated([{ ...approval({ id: 4, type: { value: 'invite', label: 'Invite' } }), user: alan }]),
        });

        expect(wrapper.get('[data-join-requests] li').text()).toContain('Grace Hopper');
        expect(wrapper.get('[data-invites] li').text()).toContain('Alan Turing');
        expect(decisions(wrapper)).toEqual([]);
        expect(wrapper.findComponent(CancelInvite).exists()).toBe(false);
    });

    it("doesn't offer anything more to a member who can only look", () => {
        signIn();
        const wrapper = show();

        expect(button(wrapper, 'Ask to join')).toBeUndefined();
        expect(wrapper.find('a[href="/circles.edit/abc123"]').exists()).toBe(false);
        expect(links(wrapper).map(([label]) => label)).not.toContain('Log in to ask to join');
        expect(wrapper.findComponent(DeleteCircle).exists()).toBe(false);
        expect(decisions(wrapper)).toEqual([]);
        expect(wrapper.findComponent(InviteDialog).exists()).toBe(false);
        expect(wrapper.find('[data-approval]').exists()).toBe(false);
        expect(wrapper.find('[data-join-request]').exists()).toBe(false);
        expect(wrapper.find('[data-join-requests]').exists()).toBe(false);
        expect(wrapper.find('[data-invites]').exists()).toBe(false);
        expect(wrapper.find('[data-channels]').exists()).toBe(false);
        expect(wrapper.find('[data-members]').exists()).toBe(false);
    });

    it("doesn't let anyone but staff decide on the circle", () => {
        signIn();

        expect(decisions(show({ approval: waiting }))).toEqual([]);
    });

    it("doesn't suggest editing a circle that wasn't approved to those who can't edit it", () => {
        signIn();
        const wrapper = show({ approval: { ...waiting, status: { value: 'rejected', label: 'Not approved' } } });

        expect(wrapper.get('[data-approval]').text()).not.toContain('Edit the circle');
    });

    it("doesn't offer to invite people until the circle can take them", async () => {
        signIn();
        const wrapper = show({ approval: waiting, pending_invites: paginated([]) }, document.body);

        expect(wrapper.find('[data-invites]').exists()).toBe(true);
        expect(wrapper.findComponent(InviteDialog).exists()).toBe(false);
        expect(await createMenu(wrapper)).toEqual(['Create a circle']);
        wrapper.unmount();
    });

    it('shows members where the channels will be', () => {
        signIn();

        expect(show({ can: { ...nothing, view_channels: true } }).get('[data-channels]').text()).toContain('Channels');
    });
});
