import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import CircleCard from '@/components/circles/CircleCard.vue';
import PaginatedList from '@/components/ui/pagination/PaginatedList.vue';
import SearchBar from '@/components/ui/search/SearchBar.vue';
import Index from '@/pages/circles/Index.vue';
import type { Circle, Loaded, Paginated } from '@/types';

type Listed = Loaded<Circle, 'members_count'>;
import { fakeRouter, groupButtons, links, locales, page, paginated, signIn } from '@tests/support';


const circles = paginated([
    { public_id: 'aaaaaaaaaa', name: 'Allotment', description: 'Grow food together.', members_count: 4, location: null },
    { public_id: 'bbbbbbbbbb', name: 'Exchange', description: 'Share skills and lend tools.', members_count: 1, location: null },
]);

const mine: Loaded<Circle, 'members_count' | 'status'> = {
    public_id: 'cccccccccc',
    name: 'Seed Exchange',
    description: 'Swapping seeds that grow well here.',
    members_count: 1,
    location: null,
    status: { value: 'pending', label: 'Waiting for approval' },
};

/**
 * The list as CircleController::index sends it: to a guest unless your_circles is given.
 */
function index(props: { circles?: Paginated<Listed>; filters?: { search: string }; your_circles?: Loaded<Circle, 'members_count' | 'status'>[] | null } = {}) {
    return mount(Index, { props: { circles, filters: { search: '' }, your_circles: null, locale: 'en', locales, ...props } });
}

function circleNames(wrapper: ReturnType<typeof index>): string[] {
    return wrapper.findAll('main h3 a').map((link) => link.text());
}

function shown(wrapper: ReturnType<typeof index>): string | undefined {
    return groupButtons(wrapper, 'Circles to show').find((tab) => tab.attributes('aria-pressed') === 'true')?.text();
}

describe('circles/Index', () => {
    it('lists the circles, each linking to its page', () => {
        const wrapper = index();

        expect(wrapper.findAll('main h3 a').map((link) => [link.text(), link.attributes('href')])).toEqual([
            ['Allotment', '/circles.show/aaaaaaaaaa'],
            ['Exchange', '/circles.show/bbbbbbbbbb'],
        ]);
    });

    it('says so when there are no circles yet', () => {
        const wrapper = index({ circles: paginated([]) });

        expect(wrapper.text()).toContain('No circles yet');
        expect(wrapper.find('main h3 a').exists()).toBe(false);
    });

    it('starts members on their own circles, with their approval status', () => {
        signIn();
        const wrapper = index({ your_circles: [mine] });

        expect(groupButtons(wrapper, 'Circles to show').map((tab) => tab.text())).toEqual(['Your circles', 'Popular']);
        expect(shown(wrapper)).toBe('Your circles');
        expect(circleNames(wrapper)).toEqual(['Seed Exchange']);
        expect(wrapper.get('[data-status]').text()).toBe('Waiting for approval');
    });

    it('switches members to the popular circles', async () => {
        signIn();
        const wrapper = index({ your_circles: [mine] });

        await groupButtons(wrapper, 'Circles to show')[1].trigger('click');

        expect(circleNames(wrapper)).toEqual(['Allotment', 'Exchange']);
    });

    it('says so when the member is in no circles yet', async () => {
        signIn();
        const wrapper = index({ your_circles: [] });

        await groupButtons(wrapper, 'Circles to show')[0].trigger('click');

        expect(wrapper.text()).toContain("You're not in any circles yet");
        expect(circleNames(wrapper)).toEqual([]);
    });

    it('searches the popular circles by name or description, reloading only the circles', () => {
        expect(index({ filters: { search: 'seed' } }).getComponent(SearchBar).props()).toEqual({
            param: 'search',
            pageName: 'page',
            search: 'seed',
            only: ['circles', 'filters'],
            label: 'Search circles',
            placeholder: 'Search circles by name or description',
        });
    });

    it('searches from the page it is on, starting the circles from their first page', async () => {
        page.url = '/?page=2';
        const wrapper = index();

        await wrapper.get('form[role="search"] input[name="search"]').setValue('seed');
        await wrapper.get('form[role="search"]').trigger('submit');

        expect(fakeRouter.get).toHaveBeenCalledExactlyOnceWith('/?search=seed', {}, { only: ['circles', 'filters'], preserveState: true, preserveScroll: true });
    });

    it('says so when no circle matches the search', () => {
        const wrapper = index({ circles: paginated([]), filters: { search: 'zz' } });

        expect(wrapper.text()).toContain('No circles match “zz”');
        expect(wrapper.text()).not.toContain('No circles yet');
    });

    it('pages through the popular circles in a list of their own, reloading only the circles and bringing the search bar back into view', async () => {
        const page = paginated(circles.data, { last_page: 2, total: 30, path: '/circles' });
        const wrapper = index({ circles: page });
        const list = wrapper.getComponent(PaginatedList);

        expect(list.props()).toEqual(expect.objectContaining({ paginator: page, label: 'Popular circles', only: ['circles'], size: 'page' }));
        // The search bar is a template ref, filled in once the page has rendered.
        await wrapper.vm.$nextTick();
        expect(list.props('anchor')).toBe(wrapper.get('form[role="search"]').element);
        expect(wrapper.get('form[role="search"]').classes()).toContain('scroll-mt-24');
        expect(list.get('[role="region"]').findAllComponents(CircleCard)).toHaveLength(2);
        expect(wrapper.get('nav[aria-label="Popular circles pages"]').find('a[href="/circles?page=2"]').exists()).toBe(true);
    });

    it("doesn't show guests the tabs, since they have no circles of their own", () => {
        const wrapper = index();

        expect(wrapper.find('[role="group"][aria-label="Circles to show"]').exists()).toBe(false);
        expect(circleNames(wrapper)).toEqual(['Allotment', 'Exchange']);
    });

    it("doesn't start members in no circles on their empty list", () => {
        signIn();

        expect(shown(index({ your_circles: [] }))).toBe('Popular');
    });

    it("doesn't move a member who is searching or paging off Popular", () => {
        signIn();

        expect(shown(index({ your_circles: [mine], filters: { search: 'seed' } }))).toBe('Popular');
        expect(shown(index({ your_circles: [mine], circles: { ...circles, current_page: 2, last_page: 2 } }))).toBe('Popular');
    });

    it("doesn't offer the search on the member's own circles, which it doesn't cover", () => {
        signIn();

        expect(index({ your_circles: [mine] }).find('form[role="search"]').exists()).toBe(false);
    });

    it('invites guests to log in or join', () => {
        expect(links(index().get('main section'))).toEqual([
            ['Log in', '/login'],
            ['Join the Circle', '/auth.register.create'],
        ]);
    });

    it('offers members the explore button instead', () => {
        signIn();
        const wrapper = index();

        expect(wrapper.get('main section button').text()).toBe('Explore Circles');
        expect(wrapper.get('main section').find('a').exists()).toBe(false);
    });

    it('starts the language switch on the current language and lets it change', async () => {
        const wrapper = index();
        const languages = () => groupButtons(wrapper, 'Language');

        expect(languages()[0].attributes('aria-pressed')).toBe('true');

        await languages()[1].trigger('click');

        expect(languages()[1].attributes('aria-pressed')).toBe('true');
    });

    it('links to the community rules', () => {
        expect(index().get('footer a').text()).toBe('Community rules');
    });
});
