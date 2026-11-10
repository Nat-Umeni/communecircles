<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

// A page's own action as a round icon link on the green header bar, styled like the Create menu's button.
defineProps<{
    href: string;
    /** Names the icon for screen readers and shows as a tooltip, e.g. "Edit circle". */
    label: string;
}>();
</script>

<template>
    <Link
        :href="href"
        :aria-label="label"
        :title="label"
        class="grid size-10 shrink-0 cursor-pointer place-items-center rounded-full bg-brand-foreground/10 text-brand-foreground/80 transition-colors hover:bg-brand-foreground/20 hover:text-brand-foreground focus-visible:ring-2 focus-visible:ring-brand-foreground focus-visible:outline-none"
    >
        <slot />
    </Link>
</template>
