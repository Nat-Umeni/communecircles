<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import type { Paginated } from '@/types';

defineProps<{
    paginator: Paginated<unknown>;
}>();
</script>

<template>
    <nav v-if="paginator.last_page > 1" aria-label="Pages" class="flex items-center justify-between gap-3">
        <Button variant="surface" size="sm" :href="paginator.prev_page_url ?? undefined" :disabled="!paginator.prev_page_url">Previous</Button>
        <p class="text-sm text-muted-foreground">Page {{ paginator.current_page }} of {{ paginator.last_page }}</p>
        <Button variant="surface" size="sm" :href="paginator.next_page_url ?? undefined" :disabled="!paginator.next_page_url">Next</Button>
    </nav>
</template>
