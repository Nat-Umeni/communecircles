<script setup>
import Card from '@/components/ui/Card.vue';
import Tag from '@/components/ui/Tag.vue';

defineProps({
    name: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    channels: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Card :title="name" :description="description" marker>
        <div class="flex flex-wrap gap-2">
            <Tag v-for="channel in channels" :key="channel">{{ channel }}</Tag>
        </div>
    </Card>
</template>
