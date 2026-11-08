<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href?: string;
}>();
</script>

<template>
    <component
        :is="href ? Link : 'span'"
        :href="href"
        :class="[
            'inline-flex items-center gap-1 rounded-full bg-secondary px-4 py-1.5 text-secondary-foreground',
            href && 'transition-colors hover:bg-highlight focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
        ]"
    >
        <span class="text-accent" aria-hidden="true">#</span>
        <slot />
    </component>
</template>
