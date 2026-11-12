<script setup lang="ts">
import { MapPin } from '@lucide/vue';
import type { Location } from '@/types';
import { locationLabel } from '@/utils/format';

defineProps<{
    location: Location;
}>();
</script>

<template>
    <p class="flex items-center gap-1.5 text-sm text-muted-foreground">
        <MapPin class="size-4 shrink-0" aria-hidden="true" />
        {{ locationLabel(location) }}
    </p>
</template>
