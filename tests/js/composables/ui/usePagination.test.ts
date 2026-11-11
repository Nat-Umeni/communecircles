import { describe, expect, it } from 'vitest';
import { ref } from 'vue';
import { usePagination } from '@/composables/ui/usePagination';
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

describe('usePagination', () => {
    it("turns the paginator's numbered links into pages and gaps, leaving out Previous and Next", () => {
        const { pages } = usePagination(() => middle);

        expect(pages.value).toEqual([
            { kind: 'page', page: 1, url: '/circles?page=1', current: false },
            { kind: 'page', page: 2, url: '/circles?page=2', current: false },
            { kind: 'gap' },
            { kind: 'page', page: 5, url: '/circles?page=5', current: false },
            { kind: 'page', page: 6, url: '/circles?page=6', current: true },
            { kind: 'page', page: 7, url: '/circles?page=7', current: false },
            { kind: 'gap' },
            { kind: 'page', page: 10, url: '/circles?page=10', current: false },
            { kind: 'page', page: 11, url: '/circles?page=11', current: false },
        ]);
    });

    it('gives the previous and next pages, or none at either end', () => {
        expect(usePagination(() => middle).previous.value).toBe('/circles?page=5');
        expect(usePagination(() => middle).next.value).toBe('/circles?page=7');

        const first = usePagination(() => paginated(['a'], { current_page: 1, last_page: 2 }));
        const last = usePagination(() => paginated(['a'], { current_page: 2, last_page: 2 }));

        expect(first.previous.value).toBeNull();
        expect(last.next.value).toBeNull();
    });

    it('follows the paginator when a new page arrives', () => {
        const paginator = ref(paginated(['a'], { current_page: 1, last_page: 2, path: '/approvals' }));
        const { pages, next } = usePagination(paginator);

        paginator.value = paginated(['b'], { current_page: 2, last_page: 2, path: '/approvals' });

        expect(pages.value.find((page) => page.kind === 'page' && page.current)).toEqual({ kind: 'page', page: 2, url: '/approvals?page=2', current: true });
        expect(next.value).toBeNull();
    });
});
