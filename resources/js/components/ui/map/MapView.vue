<script setup lang="ts">
import 'leaflet/dist/leaflet.css';
import type { LayerGroup, Map as LeafletMap, MapOptions, TileLayer } from 'leaflet';
import { onBeforeUnmount, onMounted, useTemplateRef, watch } from 'vue';
import { useTheme } from '@/composables/ui/useTheme';
import type { MapMarker, MapPoint, MapTiles } from '@/types';

const props = withDefaults(
    defineProps<{
        /** Where the map is centred, e.g. a GeoNames place. */
        center: MapPoint;
        /** How close in, from 0 (the whole world) up to the tiles' max_zoom. */
        zoom: number;
        /** The points to mark, each with a label shown on hover and read out. */
        markers?: MapMarker[];
        /** The map's accessible name, e.g. "Where Fernhill Eco Village is". */
        label: string;
        /** The map images for a light page and a dark one, with their credit, from the page (config/map.php). */
        tiles: MapTiles;
        /** Any other Leaflet map options, e.g. { scrollWheelZoom: false } for a small map on a page that scrolls. */
        options?: MapOptions;
    }>(),
    { markers: () => [], options: () => ({}) },
);

/** Each point's dot, in the accent colour with a ring, like the dot before a circle card's title. */
const DOT_CLASS = 'rounded-full bg-accent ring-4 ring-surface shadow-md';

const canvas = useTemplateRef<HTMLElement>('canvas');
const { dark } = useTheme();

let leaflet: typeof import('leaflet') | undefined;
let map: LeafletMap | undefined;
let tiles: TileLayer | undefined;
let pins: LayerGroup | undefined;
let gone = false;

function at(point: MapPoint): [number, number] {
    return [point.latitude, point.longitude];
}

// The tiles for the page's theme, in place of any shown before.
function drawTiles(): void {
    const layer = dark.value ? props.tiles.dark : props.tiles.light;

    tiles?.remove();
    tiles = leaflet!.tileLayer(layer.url, { attribution: layer.attribution, maxZoom: layer.max_zoom }).addTo(map!);
}

function markPoints(): void {
    pins!.clearLayers();
    for (const marker of props.markers) {
        pins!.addLayer(leaflet!.marker(at(marker), { title: marker.label, icon: leaflet!.divIcon({ className: DOT_CLASS, iconSize: [16, 16] }) }));
    }
}

// NOTE: Leaflet needs a browser as soon as it loads, so it's loaded here, once the map is on the page, rather than
// imported (which would break the server-side render), which also keeps it out of pages without a map. It draws
// whatever the props are by then.
onMounted(async () => {
    const loaded = await import('leaflet');

    if (gone) {
        return;
    }

    leaflet = loaded;
    map = loaded.map(canvas.value!, { ...props.options, center: at(props.center), zoom: props.zoom });
    drawTiles();
    pins = loaded.layerGroup().addTo(map);
    markPoints();
});

watch(
    () => [props.center.latitude, props.center.longitude, props.zoom],
    () => {
        if (map !== undefined) {
            map.setView(at(props.center), props.zoom);
        }
    },
);

watch([dark, () => props.tiles], () => {
    if (map !== undefined) {
        drawTiles();
    }
});

watch(
    () => props.markers,
    () => {
        if (pins !== undefined) {
            markPoints();
        }
    },
    { deep: true },
);

onBeforeUnmount(() => {
    gone = true;
    map?.remove();
});
</script>

<template>
    <div role="region" :aria-label="label" class="relative isolate overflow-hidden rounded-control bg-secondary">
        <!-- NOTE: isolate keeps Leaflet's layers (z-index 400 and up) under the page around it, such as the sticky
        header. Leaflet takes the element below over (its classes, its children); it has nothing bound, so Vue never
        touches it again. -->
        <div ref="canvas" class="absolute inset-0" />
    </div>
</template>
