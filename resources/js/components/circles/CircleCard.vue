<script setup lang="ts">
import { computed } from 'vue';
import Card from '@/components/ui/Card.vue';
import type { CircleSummary } from '@/types';

const props = defineProps<{
    circle: CircleSummary;
}>();

const members = computed(() => `${props.circle.members_count} ${props.circle.members_count === 1 ? 'member' : 'members'}`);
</script>

<template>
    <Card :title="circle.name" :description="circle.description" :href="route('circles.show', circle.public_id)" marker>
        <p class="text-sm text-muted-foreground">{{ members }}</p>
    </Card>
</template>
