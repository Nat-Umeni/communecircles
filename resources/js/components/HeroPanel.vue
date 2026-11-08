<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <section class="relative overflow-hidden rounded-panel bg-brand px-6 py-10 sm:px-10 sm:py-12">
        <svg
            viewBox="0 0 200 200"
            fill="none"
            stroke="currentColor"
            stroke-width="5"
            class="pointer-events-none absolute -top-10 -right-12 hidden size-64 text-brand-decoration sm:block"
            aria-hidden="true"
        >
            <circle cx="100" cy="62" r="42" />
            <circle cx="62" cy="100" r="42" />
            <circle cx="138" cy="100" r="42" />
            <circle cx="100" cy="138" r="42" />
        </svg>

        <div class="relative flex max-w-xl flex-col gap-4">
            <h1 class="text-3xl leading-tight font-extrabold text-brand-foreground sm:text-4xl">{{ title }}</h1>
            <p class="text-lg text-brand-muted-foreground">{{ description }}</p>
            <div class="mt-2 flex flex-wrap gap-3">
                <!-- The call-to-action buttons. -->
                <slot />
            </div>
        </div>
    </section>
</template>
