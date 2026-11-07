<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
    // Use a plain <a> for links that leave the app.
    external: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <component
        :is="external ? 'a' : Link"
        :href="href"
        class="font-semibold text-primary underline underline-offset-4 transition-all hover:decoration-2 focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
    >
        <slot />
    </component>
</template>
