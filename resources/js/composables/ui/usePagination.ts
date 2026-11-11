import { computed, toValue } from 'vue';
import type { ComputedRef, MaybeRefOrGetter } from 'vue';
import type { Paginated } from '@/types';

/** A numbered page to link to (or the current one), or the "…" between numbers Laravel leaves out. */
export type PageItem = { kind: 'page'; page: number; url: string; current: boolean } | { kind: 'gap' };

/**
 * A paginator's pages to show, from the links Laravel sends (so their URLs keep any other query, e.g. another list's
 * page or a search), and the previous and next pages' URLs, or null at either end.
 */
export function usePagination(paginator: MaybeRefOrGetter<Paginated<unknown>>): {
    pages: ComputedRef<PageItem[]>;
    previous: ComputedRef<string | null>;
    next: ComputedRef<string | null>;
} {
    return {
        // Laravel's first and last links are Previous and Next; the numbers and gaps are in between.
        pages: computed(() =>
            toValue(paginator)
                .links.slice(1, -1)
                .map((link): PageItem => (link.page && link.url ? { kind: 'page', page: link.page, url: link.url, current: link.active } : { kind: 'gap' })),
        ),
        previous: computed(() => toValue(paginator).prev_page_url),
        next: computed(() => toValue(paginator).next_page_url),
    };
}
