/**
 * One of a paginator's links, as Laravel's LengthAwarePaginator sends them: a numbered page, the "…" gap between
 * numbers (no page, no url), or the Previous and Next links at either end (page null where there's none).
 */
export interface PaginatorLink {
    url: string | null;
    label: string;
    page?: number | null;
    active: boolean;
}

/**
 * A page of results from Laravel's paginator (LengthAwarePaginator), as Inertia receives it.
 */
export interface Paginated<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    /** The position of the first item on this page, or null when it's empty. */
    from: number | null;
    last_page: number;
    last_page_url: string;
    links: PaginatorLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    /** The position of the last item on this page, or null when it's empty. */
    to: number | null;
    total: number;
}
