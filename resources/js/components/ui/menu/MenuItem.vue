<script setup lang="ts">
import type { Method } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';

defineProps<{
    /** With no href the item is a plain button. */
    href?: string;
    /** The request method for the href, e.g. "post" to log out. */
    method?: Method;
}>();

const classes =
    'flex w-full cursor-pointer items-center gap-2 rounded-[calc(var(--radius-control)-4px)] px-3 py-2 text-left font-heading font-bold whitespace-nowrap text-heading transition-colors hover:bg-secondary focus-visible:bg-secondary focus-visible:outline-none';
</script>

<template>
    <!-- NOTE: tabindex -1 because the menu moves focus between items with the arrow keys; Tab leaves the menu. -->
    <!-- NOTE: anything but a GET renders as a <button>, since a link shouldn't send a POST. -->
    <Link v-if="href" :href="href" :method="method" :as="method && method !== 'get' ? 'button' : 'a'" role="menuitem" tabindex="-1" :class="classes">
        <slot />
    </Link>
    <button v-else type="button" role="menuitem" tabindex="-1" :class="classes">
        <slot />
    </button>
</template>
