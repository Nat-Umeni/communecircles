<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PlaceSearch from '@/components/circles/PlaceSearch.vue';
import Button from '@/components/ui/Button.vue';
import FormField from '@/components/ui/form/FormField.vue';
import Input from '@/components/ui/form/Input.vue';
import Select from '@/components/ui/form/Select.vue';
import Textarea from '@/components/ui/form/Textarea.vue';
import MapView from '@/components/ui/map/MapView.vue';
import type { Circle, MapPoint, MapTiles, Option, Place } from '@/types';

const props = defineProps<{
    /** Where the form goes: a post to circles.store for a new circle, a put to circles.update for an edit. */
    method: 'post' | 'put';
    url: string;
    countries: Option[];
    /** The map images for picking the exact spot, from the page (config/map.php). */
    mapTiles: MapTiles;
    /** The circle being edited, whose details fill the form. */
    circle?: Circle;
    submitLabel: string;
}>();

/** Where the spot map starts with no spot or town: the whole world. */
const WORLD: MapPoint = { latitude: 20, longitude: 0 };
const WORLD_ZOOM = 2;
/** Close enough to find the land around a town. */
const TOWN_ZOOM = 13;
const SPOT_ZOOM = 14;

const location = props.circle?.location;

// The chosen town or village; the form sends its geonames_id.
const place = ref<Place | null>(location?.place ?? null);

// Field names match the validation in app/Http/Requests/Circle/CircleRequest.php and its subclasses.
const form = useForm({
    name: props.circle?.name ?? '',
    description: props.circle?.description ?? '',
    geonames_id: place.value?.geonames_id ?? null,
    location_name: location?.name ?? '',
    country_code: location?.country_code ?? '',
    latitude: location?.coordinates ? String(location.coordinates.latitude) : '',
    longitude: location?.coordinates ? String(location.coordinates.longitude) : '',
});

/** A typed coordinate as a number, or null while it's empty, not a number, or past the limit (90 or 180). */
function coordinate(text: string, limit: number): number | null {
    const value = Number(text);

    return text.trim() === '' || !Number.isFinite(value) || Math.abs(value) > limit ? null : value;
}

// The exact spot the two fields give, once both are numbers on the globe, and its pin.
const spot = computed<MapPoint | null>(() => {
    const latitude = coordinate(form.latitude, 90);
    const longitude = coordinate(form.longitude, 180);

    return latitude === null || longitude === null ? null : { latitude, longitude };
});
const spotMarkers = computed(() => (spot.value ? [{ ...spot.value, label: 'The exact spot' }] : []));

// Where the spot map looks: the spot, else the town, else the whole world. It moves when a town is chosen or a typed
// spot is complete, but not with each click, so picking doesn't jump the map about.
function startingView(): { center: MapPoint; zoom: number } {
    if (spot.value) {
        return { center: spot.value, zoom: SPOT_ZOOM };
    }
    if (place.value) {
        return { center: place.value, zoom: TOWN_ZOOM };
    }

    return { center: WORLD, zoom: WORLD_ZOOM };
}

const view = ref(startingView());

function choosePlace(chosen: Place | null): void {
    place.value = chosen;
    form.geonames_id = chosen?.geonames_id ?? null;
    if (chosen) {
        view.value = { center: chosen, zoom: TOWN_ZOOM };
    }
}

/** A coordinate to the 6 decimal places the database keeps (about 10 cm). */
function rounded(value: number): string {
    return String(Math.round(value * 1e6) / 1e6);
}

function pickSpot(point: MapPoint): void {
    form.latitude = rounded(point.latitude);
    form.longitude = rounded(point.longitude);
}

function showTypedSpot(): void {
    if (spot.value) {
        view.value = { center: spot.value, zoom: SPOT_ZOOM };
    }
}

function removeSpot(): void {
    form.latitude = '';
    form.longitude = '';
}

function submit(): void {
    form.submit(props.method, props.url);
}
</script>

<template>
    <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
        <FormField v-slot="field" label="Name" :error="form.errors.name">
            <Input v-bind="field" v-model="form.name" name="name" maxlength="255" required />
        </FormField>

        <FormField v-slot="field" label="Description" hint="What the circle is for, in a sentence or two." :error="form.errors.description">
            <Textarea v-bind="field" v-model="form.description" name="description" maxlength="500" required />
        </FormField>

        <fieldset class="flex flex-col gap-5">
            <legend class="mb-1 font-semibold">Where is it? <span class="font-normal text-muted-foreground">(optional)</span></legend>

            <FormField v-slot="field" label="Nearest town or village" hint="Start typing its name, then choose it from the list." :error="form.errors.geonames_id">
                <PlaceSearch v-bind="field" :model-value="place" @update:model-value="choosePlace" />
            </FormField>

            <FormField v-slot="field" label="Your place's name" hint="A farm or site, for example." :error="form.errors.location_name">
                <Input v-bind="field" v-model="form.location_name" name="location_name" maxlength="255" />
            </FormField>

            <FormField v-if="!place" v-slot="field" label="Country" hint="Needed for a place with no town or village." :error="form.errors.country_code">
                <Select v-bind="field" v-model="form.country_code" name="country_code" :options="countries" placeholder="Choose a country" />
            </FormField>

            <div class="flex flex-col gap-3">
                <div class="flex flex-col gap-1">
                    <p class="font-semibold">Exact spot <span class="font-normal text-muted-foreground">(optional)</span></p>
                    <p class="text-sm text-muted-foreground">
                        Click the map where your land is, or type its latitude and longitude (a right-click in Google Maps copies them). Only the circle's
                        members and staff see it; everyone else sees the town it's near.
                    </p>
                </div>
                <MapView
                    :center="view.center"
                    :zoom="view.zoom"
                    :markers="spotMarkers"
                    label="Map to pick the exact spot"
                    :tiles="mapTiles"
                    :options="{ scrollWheelZoom: false }"
                    class="h-64"
                    @pick="pickSpot"
                />
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField v-slot="field" label="Latitude" :error="form.errors.latitude">
                        <Input v-bind="field" v-model="form.latitude" name="latitude" placeholder="e.g. 50.4271" autocomplete="off" @change="showTypedSpot" />
                    </FormField>
                    <FormField v-slot="field" label="Longitude" :error="form.errors.longitude">
                        <Input v-bind="field" v-model="form.longitude" name="longitude" placeholder="e.g. -3.7124" autocomplete="off" @change="showTypedSpot" />
                    </FormField>
                </div>
                <Button v-if="form.latitude !== '' || form.longitude !== ''" type="button" variant="ghost" size="sm" class="self-start" data-remove-spot @click="removeSpot">
                    Remove the exact spot
                </Button>
            </div>
        </fieldset>

        <div class="mt-2 flex flex-wrap items-center gap-3">
            <Button type="submit" size="lg" :disabled="form.processing">{{ submitLabel }}</Button>
            <!-- Other ways out of the form, e.g. Cancel. -->
            <slot name="actions" />
        </div>
    </form>
</template>
