import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Index from '@/pages/circles/Index.vue';
import type { CircleSummary, Paginated } from '@/types';
import { groupButtons, links, locales, signIn } from '@tests/support';

function circlesPage(data: CircleSummary[]): Paginated<CircleSummary> {
    return { data, current_page: 1, last_page: 1, total: data.length, prev_page_url: null, next_page_url: null };
}

const circles = circlesPage([
    { public_id: 'aaaaaaaaaa', name: 'Allotment', description: 'Grow food together.', members_count: 4, location: null },
    { public_id: 'bbbbbbbbbb', name: 'Exchange', description: 'Share skills and lend tools.', members_count: 1, location: null },
]);

function index(page: Paginated<CircleSummary> = circles) {
    return mount(Index, { props: { circles: page, filters: { search: '' }, locale: 'en', locales } });
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
        const wrapper = index(circlesPage([]));

        expect(wrapper.text()).toContain('No circles yet');
        expect(wrapper.find('main h3 a').exists()).toBe(false);
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
