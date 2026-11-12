<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    /** Makes the title a link, e.g. to the circle the card is about. */
    href?: string;
    description?: string;
    /** Shows the small accent dot before the title, as on the circle cards. */
    marker?: boolean;
}>();
</script>

<template>
    <section class="@container rounded-card border border-border bg-surface p-6 shadow-xs">
        <!-- NOTE: a side (e.g. a map) sits beside the content, half each, once the card is wide enough, and under it
        before then. min-w-0 keeps wide content (a table) scrolling in its own box rather than widening the card. -->
        <div :class="['grid grid-cols-1 gap-6', { '@2xl:grid-cols-2': $slots.side }]">
            <div class="flex min-w-0 flex-col gap-4">
                <header v-if="title || description || $slots.aside" class="flex flex-col gap-1">
                    <!-- NOTE: the aside (e.g. a search for the card's list) sits at the end of the title's row, and
                    wraps under the title when there isn't room beside it. -->
                    <div v-if="title || $slots.aside" class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3">
                        <h3 v-if="title" class="flex items-center gap-3 text-xl font-bold">
                            <span v-if="marker" class="size-3 shrink-0 rounded-full bg-accent" aria-hidden="true" />
                            <Link
                                v-if="href"
                                :href="href"
                                class="cursor-pointer rounded-control hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                {{ title }}
                            </Link>
                            <template v-else>{{ title }}</template>
                        </h3>
                        <slot name="aside" />
                    </div>
                    <p v-if="description" class="text-muted-foreground">{{ description }}</p>
                </header>
                <slot />
            </div>
            <slot name="side" />
        </div>
    </section>
</template>
