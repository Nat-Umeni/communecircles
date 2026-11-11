import type { Paginated, PaginatorLink } from '@/types';

/**
 * A page of results as Laravel's paginator sends it, links included, e.g. paginated(circles, { current_page: 2,
 * last_page: 3 }). URLs are `${path}?page=N` (path defaults to /items), and every page gets a numbered link; pass
 * `links` to test a window with gaps.
 */
export function paginated<T>(data: T[], overrides: Partial<Paginated<T>> = {}): Paginated<T> {
    const current = overrides.current_page ?? 1;
    const last = overrides.last_page ?? 1;
    const path = overrides.path ?? '/items';
    const perPage = overrides.per_page ?? Math.max(data.length, 1);
    const url = (page: number): string => `${path}?page=${page}`;
    const previous = current > 1 ? url(current - 1) : null;
    const next = current < last ? url(current + 1) : null;
    const links: PaginatorLink[] = [
        { url: previous, label: '&laquo; Previous', page: current > 1 ? current - 1 : null, active: false },
        ...Array.from({ length: last }, (_, index) => ({ url: url(index + 1), label: String(index + 1), page: index + 1, active: index + 1 === current })),
        { url: next, label: 'Next &raquo;', page: current < last ? current + 1 : null, active: false },
    ];

    return {
        data,
        current_page: current,
        first_page_url: url(1),
        from: data.length > 0 ? (current - 1) * perPage + 1 : null,
        last_page: last,
        last_page_url: url(last),
        links,
        next_page_url: next,
        path,
        per_page: perPage,
        prev_page_url: previous,
        to: data.length > 0 ? (current - 1) * perPage + data.length : null,
        total: data.length,
        ...overrides,
    };
}
