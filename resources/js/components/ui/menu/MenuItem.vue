<script setup lang="ts">
import type { Method } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';

defineProps<{
    /** With no href the item is a plain button. */
    href?: string;
    /** The request method for the href, e.g. "post" to log out. */
    method?: Method;
}>();

// NOTE: tabindex -1 on the items because the menu moves focus between them with the arrow keys; Tab leaves the menu.
// NOTE: for anything but a GET, Inertia's Link renders a <button> itself, since a link shouldn't send a POST.
const classes =
    'flex w-full cursor-pointer items-center gap-2 rounded-[calc(var(--radius-control)-4px)] px-3 py-2 text-left font-heading font-bold whitespace-nowrap text-heading transition-colors hover:bg-secondary focus-visible:bg-secondary focus-visible:outline-none';
</script>

<template>
    <Link v-if="href" :href="href" :method="method" role="menuitem" tabindex="-1" :class="classes">
        <slot />
    </Link>
    <button v-else type="button" role="menuitem" tabindex="-1" :class="classes">
        <slot />
    </button>
</template>
