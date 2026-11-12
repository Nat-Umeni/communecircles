<script setup lang="ts">
import { MapPin } from '@lucide/vue';
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 rounded-control border-2 border-dashed border-border bg-secondary/50 p-6 text-center text-muted-foreground">
        <!-- Where a map would be, with a message in its place, e.g. for something that isn't on the map yet. -->
        <MapPin class="size-8" aria-hidden="true" />
        <slot />
    </div>
</template>
