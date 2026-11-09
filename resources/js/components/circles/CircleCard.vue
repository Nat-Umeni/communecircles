<script setup lang="ts">
import { computed } from 'vue';
import CircleLocation from '@/components/circles/CircleLocation.vue';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import type { CircleOfMine, CircleSummary } from '@/types';

const props = defineProps<{
    /** One of the member's own circles carries its approval status, shown as a badge until it's approved. */
    circle: CircleSummary | CircleOfMine;
}>();

const members = computed(() => `${props.circle.members_count} ${props.circle.members_count === 1 ? 'member' : 'members'}`);

const status = computed(() => ('status' in props.circle && props.circle.status.value !== 'approved' ? props.circle.status : null));
</script>

<template>
    <Card :title="circle.name" :description="circle.description" :href="route('circles.show', circle.public_id)" marker>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <Badge v-if="status" data-status :variant="status.value === 'rejected' ? 'danger' : 'warning'">{{ status.label }}</Badge>
            <CircleLocation v-if="circle.location" :location="circle.location" />
            <p class="text-sm text-muted-foreground">{{ members }}</p>
        </div>
    </Card>
</template>
