<script setup lang="ts">
import type { Option } from '@/types';

defineProps<{
    options: Option[];
    /** Shown first while nothing is chosen, e.g. "Choose a country"; it can't be chosen itself. */
    placeholder?: string;
}>();

const model = defineModel<string>({ default: '' });
</script>

<template>
    <select
        v-model="model"
        class="h-12 w-full cursor-pointer rounded-control border border-border bg-surface px-4 text-foreground transition-colors focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 focus-visible:outline-none disabled:cursor-default disabled:opacity-50 aria-invalid:border-danger aria-invalid:focus-visible:ring-danger/30"
    >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>
</template>
