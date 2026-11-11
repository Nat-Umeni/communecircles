/**
 * A page of results from Laravel's paginator (LengthAwarePaginator), as Inertia receives it.
 */
export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}
