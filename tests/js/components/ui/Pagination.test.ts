import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Pagination from '@/components/ui/Pagination.vue';
import type { Paginated } from '@/types';

function page(overrides: Partial<Paginated<string>>): Paginated<string> {
    return { data: [], current_page: 2, last_page: 3, total: 60, prev_page_url: '/circles?page=1', next_page_url: '/circles?page=3', ...overrides };
}

describe('Pagination', () => {
    it('links to the previous and next pages around which page this is', () => {
        const wrapper = mount(Pagination, { props: { paginator: page({}) } });

        expect(wrapper.get('nav').attributes('aria-label')).toBe('Pages');
        expect(wrapper.text()).toContain('Page 2 of 3');
        expect(wrapper.findAll('a').map((link) => [link.text(), link.attributes('href')])).toEqual([
            ['Previous', '/circles?page=1'],
            ['Next', '/circles?page=3'],
        ]);
    });

    it('disables the direction there is no page in', () => {
        const first = mount(Pagination, { props: { paginator: page({ current_page: 1, prev_page_url: null }) } });
        const last = mount(Pagination, { props: { paginator: page({ current_page: 3, next_page_url: null }) } });

        expect(first.get('button').text()).toBe('Previous');
        expect(first.get('button').attributes('disabled')).toBeDefined();
        expect(last.get('button').text()).toBe('Next');
        expect(last.get('button').attributes('disabled')).toBeDefined();
    });

    it('shows nothing when everything fits on one page', () => {
        const wrapper = mount(Pagination, { props: { paginator: page({ current_page: 1, last_page: 1, prev_page_url: null, next_page_url: null }) } });

        expect(wrapper.find('nav').exists()).toBe(false);
    });
});
