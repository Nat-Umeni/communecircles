<script setup lang="ts">
import Badge from '@/components/ui/Badge.vue';

// How many of something there are, as a small grey pill, e.g. "3 members".
defineProps<{
    count: number;
    /** The word for one, e.g. "member". */
    singular: string;
    /** The word for none or more than one, e.g. "members". */
    plural: string;
}>();
</script>

<template>
    <Badge variant="secondary" data-count>{{ count }} {{ count === 1 ? singular : plural }}</Badge>
</template>
