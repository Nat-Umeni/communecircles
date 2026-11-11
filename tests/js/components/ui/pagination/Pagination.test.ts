import { Link } from '@inertiajs/vue3';
import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Pagination from '@/components/ui/pagination/Pagination.vue';
import type { Paginated } from '@/types';
import { paginated } from '@tests/support';

/**
 * Page 6 of 11 with Laravel's window of one page either side: 1 2 … 5 6 7 … 10 11.
 */
const middle: Paginated<string> = paginated(['a'], {
    current_page: 6,
    last_page: 11,
    path: '/circles',
    links: [
        { url: '/circles?page=5', label: '&laquo; Previous', page: 5, active: false },
        { url: '/circles?page=1', label: '1', page: 1, active: false },
        { url: '/circles?page=2', label: '2', page: 2, active: false },
        { url: null, label: '...', active: false },
        { url: '/circles?page=5', label: '5', page: 5, active: false },
        { url: '/circles?page=6', label: '6', page: 6, active: true },
        { url: '/circles?page=7', label: '7', page: 7, active: false },
        { url: null, label: '...', active: false },
        { url: '/circles?page=10', label: '10', page: 10, active: false },
        { url: '/circles?page=11', label: '11', page: 11, active: false },
        { url: '/circles?page=7', label: 'Next &raquo;', page: 7, active: false },
    ],
});

function pagination(paginator: Paginated<unknown>, props: { label?: string; only?: string[] } = {}): VueWrapper {
    return mount(Pagination, { props: { paginator, ...props } });
}

describe('Pagination', () => {
    it('shows the page numbers with gaps, the current one marked and not a link', () => {
        const wrapper = pagination(middle);
        const pages = wrapper.findAll('[data-pages] li');

        expect(pages.map((page) => page.text())).toEqual(['1', '2', '…', '5', '6', '7', '…', '10', '11']);
        expect(wrapper.get('[aria-current="page"]').text()).toBe('6');
        expect(wrapper.get('[aria-current="page"]').element.tagName).not.toBe('A');
        expect(pages[0].get('a').attributes('href')).toBe('/circles?page=1');
        expect(pages[2].find('a').exists()).toBe(false);
    });

    it('links to the previous and next pages, and says which page this is on a phone', () => {
        const wrapper = pagination(middle);
        const links = wrapper.findAll('a');

        expect([links[0].text(), links[0].attributes('href')]).toEqual(['Previous', '/circles?page=5']);
        expect([links.at(-1)!.text(), links.at(-1)!.attributes('href')]).toEqual(['Next', '/circles?page=7']);
        expect(wrapper.get('[data-position]').text()).toBe('Page 6 of 11');
    });

    it('disables the direction there is no page in', () => {
        const first = pagination(paginated(['a'], { current_page: 1, last_page: 3 }));
        const last = pagination(paginated(['a'], { current_page: 3, last_page: 3 }));

        expect(first.get('button').text()).toBe('Previous');
        expect(first.get('button').attributes('disabled')).toBeDefined();
        expect(last.get('button').text()).toBe('Next');
        expect(last.get('button').attributes('disabled')).toBeDefined();
    });

    it('is named for the list it pages', () => {
        expect(pagination(middle).get('nav').attributes('aria-label')).toBe('Pages');
        expect(pagination(middle, { label: 'Members pages' }).get('nav').attributes('aria-label')).toBe('Members pages');
    });

    it('keeps the page where it is and its state, reloading only the list it is told to', () => {
        const everyLink = (wrapper: VueWrapper) => wrapper.findAllComponents(Link).map((link) => link.props());

        expect(everyLink(pagination(middle, { only: ['members'] }))).not.toHaveLength(0);
        for (const link of everyLink(pagination(middle, { only: ['members'] }))) {
            expect(link).toEqual(expect.objectContaining({ only: ['members'], preserveScroll: true, preserveState: true }));
        }
        for (const link of everyLink(pagination(middle))) {
            expect(link).toEqual(expect.objectContaining({ only: [], preserveScroll: true, preserveState: true }));
        }
    });

    it('shows nothing when everything fits on one page', () => {
        expect(pagination(paginated(['a'])).find('nav').exists()).toBe(false);
    });
});
