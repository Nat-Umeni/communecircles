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
    <section class="flex flex-col gap-4 rounded-card border border-border bg-surface p-6 shadow-xs">
        <header v-if="title || description" class="flex flex-col gap-1">
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
            <p v-if="description" class="text-muted-foreground">{{ description }}</p>
        </header>
        <slot />
    </section>
</template>
