<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from '@/components/ui/Button.vue';
import { usePagination } from '@/composables/ui/usePagination';
import type { Paginated } from '@/types';

const props = withDefaults(
    defineProps<{
        paginator: Paginated<unknown>;
        /** The nav's accessible name, so several lists on one page are told apart, e.g. "Members pages". */
        label?: string;
        /** The props a page reloads, e.g. ['members'], so paging one list leaves the rest of the page alone. */
        only?: string[];
    }>(),
    { label: 'Pages', only: () => [] },
);

const { pages, previous, next } = usePagination(() => props.paginator);

// NOTE: paging keeps the page where it is and its state (a search box, a tab); the list's own scroll goes back to the
// top in PaginatedList. The numbers show once the paginator itself is wide enough, so half a row keeps the phone layout.
const visit = computed(() => ({ only: props.only, preserveScroll: true, preserveState: true }));
</script>

<template>
    <nav v-if="paginator.last_page > 1" :aria-label="label" class="@container">
        <div class="flex items-center justify-between gap-3">
            <Button variant="surface" size="sm" :href="previous ?? undefined" :disabled="!previous" v-bind="previous ? visit : {}">Previous</Button>
            <p class="text-sm text-muted-foreground @xl:hidden" data-position>Page {{ paginator.current_page }} of {{ paginator.last_page }}</p>
            <ol class="hidden items-center gap-1 @xl:flex" data-pages>
                <li v-for="(item, index) in pages" :key="index">
                    <span v-if="item.kind === 'gap'" class="px-1 text-muted-foreground" aria-hidden="true">…</span>
                    <span
                        v-else-if="item.current"
                        aria-current="page"
                        class="grid h-9 min-w-9 place-items-center rounded-control bg-primary px-2 font-heading font-bold text-primary-foreground"
                    >
                        {{ item.page }}
                    </span>
                    <Link
                        v-else
                        :href="item.url"
                        v-bind="visit"
                        class="grid h-9 min-w-9 cursor-pointer place-items-center rounded-control px-2 font-heading font-bold text-heading transition-colors hover:bg-secondary focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        {{ item.page }}
                    </Link>
                </li>
            </ol>
            <Button variant="surface" size="sm" :href="next ?? undefined" :disabled="!next" v-bind="next ? visit : {}">Next</Button>
        </div>
    </nav>
</template>
