<script setup lang="ts">
import Card from '@/components/ui/Card.vue';
import Tag from '@/components/ui/Tag.vue';
import type { Circle } from '@/types';

defineProps<Circle>();
</script>

<template>
    <Card :title="name" :description="description" marker>
        <div class="flex flex-wrap gap-2">
            <Tag v-for="channel in channels" :key="channel">{{ channel }}</Tag>
        </div>
    </Card>
</template>
