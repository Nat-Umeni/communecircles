<script setup>
import { Link } from '@inertiajs/vue3';
import BrandIcon from '@/components/ui/BrandIcon.vue';

defineProps({
    href: {
        type: String,
        default: null,
    },
    icon: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <component
        :is="href ? Link : 'span'"
        :href="href ?? undefined"
        :aria-current="active ? 'page' : undefined"
        :class="[
            'flex cursor-pointer items-center gap-3 rounded-control px-3 py-2 font-heading text-lg font-bold transition-colors',
            active ? 'bg-highlight text-highlight-foreground' : 'text-heading hover:bg-secondary',
            href && 'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
        ]"
    >
        <BrandIcon :name="icon" :size="36" />
        <slot />
    </component>
</template>
