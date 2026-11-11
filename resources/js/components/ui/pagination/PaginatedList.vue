<script setup lang="ts">
import { useTemplateRef, watch } from 'vue';
import Pagination from '@/components/ui/pagination/Pagination.vue';
import type { Paginated } from '@/types';

const props = withDefaults(
    defineProps<{
        paginator: Paginated<unknown>;
        /** Names the list's scroll area, and its pages ("… pages"), e.g. "Members". */
        label: string;
        /** The props a page reloads, e.g. ['members']; see Pagination. */
        only?: string[];
        /** "card" for a list inside a card (up to 24rem high), "page" for a page's main list (up to 70% of the screen). */
        size?: 'card' | 'page';
        /** What to bring into view for a new page instead of the list itself, e.g. a search bar above it. */
        anchor?: HTMLElement | null;
    }>(),
    { only: () => [], size: 'card', anchor: null },
);

// A list scrolls on its own (both ways, so a wide table scrolls sideways here too), so its pages stay in view below
// it. Paging keeps the page where it is, so for the new page the list glides back to its top, and its top edge (or the
// anchor) comes into view if it was above the screen; give either scroll-mt-24 to keep it clear of the sticky header.
// Straight there for anyone whose system asks for less motion.
const area = useTemplateRef<HTMLElement>('area');

watch(
    () => props.paginator.current_page,
    () => {
        const behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';

        area.value!.scrollTo({ top: 0, behavior });
        (props.anchor ?? area.value!).scrollIntoView({ block: 'nearest', behavior });
    },
);
</script>

<template>
    <div class="flex flex-col gap-4">
        <div
            ref="area"
            role="region"
            :aria-label="label"
            tabindex="0"
            :class="[
                'scroll-mt-24 overflow-auto rounded-control focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                size === 'card' ? 'max-h-96' : 'max-h-[70vh]',
            ]"
        >
            <slot />
        </div>
        <Pagination :paginator="paginator" :label="`${label} pages`" :only="only" />
    </div>
</template>
