<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import CircleForm from '@/components/circles/CircleForm.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import type { Circle, MapTiles, Option } from '@/types';

// From CircleController::edit.
defineProps<{
    circle: Circle;
    countries: Option[];
    /** For the map that picks the circle's exact spot (config/map.php). */
    map_tiles: MapTiles;
}>();
</script>

<template>
    <AppLayout>
        <Head :title="`Edit ${circle.name}`" />

        <Card :title="`Edit ${circle.name}`">
            <CircleForm method="put" :url="route('circles.update', circle.public_id)" :circle="circle" :countries="countries" :map-tiles="map_tiles" submit-label="Save changes">
                <template #actions>
                    <Button variant="ghost" size="lg" :href="route('circles.show', circle.public_id)">Cancel</Button>
                </template>
            </CircleForm>
        </Card>
    </AppLayout>
</template>
