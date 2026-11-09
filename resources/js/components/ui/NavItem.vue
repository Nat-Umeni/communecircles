<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';
import BrandIcon from '@/components/ui/BrandIcon.vue';

defineProps<{
    href?: string;
    /** A brand icon's name (public/images/icons), or a Lucide icon component for items with no brand icon. */
    icon: string | Component;
    active?: boolean;
}>();
</script>

<template>
    <component
        :is="href ? Link : 'span'"
        :href="href"
        :aria-current="active ? 'page' : undefined"
        :class="[
            'flex cursor-pointer items-center gap-3 rounded-control px-3 py-2 font-heading text-lg font-bold transition-colors',
            active ? 'bg-highlight text-highlight-foreground' : 'text-heading hover:bg-secondary',
            href && 'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
        ]"
    >
        <BrandIcon v-if="typeof icon === 'string'" :name="icon" :size="36" />
        <span v-else class="grid size-9 shrink-0 place-items-center">
            <component :is="icon" class="size-6" aria-hidden="true" />
        </span>
        <slot />
    </component>
</template>
