<script setup>
import { Monitor, Moon, Sun } from '@lucide/vue';
import SegmentedControl from '@/components/ui/SegmentedControl.vue';
import { useTheme } from '@/composables/useTheme';

defineProps({
    tone: {
        type: String,
        default: 'default',
    },
});

const { theme } = useTheme();

const options = [
    { value: 'light', label: 'Light theme', icon: Sun },
    { value: 'system', label: 'Match system theme', icon: Monitor },
    { value: 'dark', label: 'Dark theme', icon: Moon },
];
</script>

<template>
    <SegmentedControl v-model="theme" :options="options" shape="pill" :tone="tone" label="Colour theme" />
</template>
