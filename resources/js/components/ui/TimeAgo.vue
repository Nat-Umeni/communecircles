<script setup lang="ts">
import { computed } from 'vue';
import type { Timestamp } from '@/types';

const props = defineProps<{
    datetime: Timestamp;
}>();

// Each unit with its length in seconds, largest first. Months and years are taken as 30 and 365 days.
const units: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 365 * 24 * 60 * 60],
    ['month', 30 * 24 * 60 * 60],
    ['week', 7 * 24 * 60 * 60],
    ['day', 24 * 60 * 60],
    ['hour', 60 * 60],
    ['minute', 60],
    ['second', 1],
];

const format = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

// "3 days ago", in the largest whole unit; "yesterday" and "now" where English has a word for it.
const text = computed(() => {
    const seconds = (new Date(props.datetime).getTime() - Date.now()) / 1000;
    const [unit, length] = units.find(([, unitLength]) => Math.abs(seconds) >= unitLength) ?? ['second', 1];

    return format.format(Math.trunc(seconds / length), unit);
});
</script>

<template>
    <time :datetime="datetime">{{ text }}</time>
</template>
