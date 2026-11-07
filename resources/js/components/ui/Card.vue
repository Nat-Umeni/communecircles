<script setup>
defineProps({
    title: {
        type: String,
        default: null,
    },
    description: {
        type: String,
        default: null,
    },
    // Shows the small accent dot before the title, as on the circle cards.
    marker: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <section class="flex flex-col gap-4 rounded-card border border-border bg-surface p-6 shadow-xs">
        <header v-if="title || description" class="flex flex-col gap-1">
            <h3 v-if="title" class="flex items-center gap-3 text-xl font-bold">
                <span v-if="marker" class="size-3 shrink-0 rounded-full bg-accent" aria-hidden="true" />
                {{ title }}
            </h3>
            <p v-if="description" class="text-muted-foreground">{{ description }}</p>
        </header>
        <slot />
    </section>
</template>
