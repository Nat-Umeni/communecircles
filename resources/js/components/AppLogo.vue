<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link :href="href" class="flex items-center gap-3 rounded-control focus-visible:ring-2 focus-visible:ring-brand-foreground focus-visible:outline-none">
        <!-- The mark has white gaps between its circles, so its tile stays white in dark mode too. -->
        <span class="grid size-10 shrink-0 place-items-center rounded-control bg-white p-1 lg:size-12">
            <img src="/images/logo-mark.png" alt="" width="40" height="40" class="size-full" />
        </span>
        <span class="font-heading text-lg tracking-[0.1em] whitespace-nowrap uppercase lg:text-2xl">
            <span class="font-extrabold">Commune</span> <span class="font-semibold">Circles</span>
        </span>
    </Link>
</template>
