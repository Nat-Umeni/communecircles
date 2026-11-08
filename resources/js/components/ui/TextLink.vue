<script setup lang="ts">
import type { Method } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';

defineProps<{
    href: string;
    /** Use a plain <a> for links that leave the app. */
    external?: boolean;
    /** The request method, e.g. "post" to log out. */
    method?: Method;
}>();

// NOTE: for anything but a GET, Inertia's Link renders a <button> itself, since a link shouldn't send a POST.
</script>

<template>
    <component
        :is="external ? 'a' : Link"
        :href="href"
        :method="external ? undefined : method"
        class="font-semibold text-primary underline underline-offset-4 transition-all hover:decoration-2 focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
    >
        <slot />
    </component>
</template>
