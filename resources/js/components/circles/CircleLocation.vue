<script setup lang="ts">
import { MapPin } from '@lucide/vue';
import { computed } from 'vue';
import type { Location } from '@/types';

const props = defineProps<{
    location: Location;
}>();

// "Fernhill Farm, near Totnes, England, United Kingdom", "Totnes, England, United Kingdom", or, for a place GeoNames
// doesn't know, "Fernhill Farm, United Kingdom".
const text = computed(() => {
    const { name, place, country_name: country } = props.location;

    if (place === null) {
        return `${name}, ${country}`;
    }

    const town = [place.name, place.region, country].filter(Boolean).join(', ');

    return name === null ? town : `${name}, near ${town}`;
});
</script>

<template>
    <p class="flex items-center gap-1.5 text-sm text-muted-foreground">
        <MapPin class="size-4 shrink-0" aria-hidden="true" />
        {{ text }}
    </p>
</template>
