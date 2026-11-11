<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PlaceSearch from '@/components/circles/PlaceSearch.vue';
import Button from '@/components/ui/Button.vue';
import FormField from '@/components/ui/form/FormField.vue';
import Input from '@/components/ui/form/Input.vue';
import Select from '@/components/ui/form/Select.vue';
import Textarea from '@/components/ui/form/Textarea.vue';
import type { Circle, Option, Place } from '@/types';

const props = defineProps<{
    /** Where the form goes: a post to circles.store for a new circle, a put to circles.update for an edit. */
    method: 'post' | 'put';
    url: string;
    countries: Option[];
    /** The circle being edited, whose details fill the form. */
    circle?: Circle;
    submitLabel: string;
}>();

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
});

function choosePlace(chosen: Place | null): void {
    place.value = chosen;
    form.geonames_id = chosen?.geonames_id ?? null;
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
        </fieldset>

        <div class="mt-2 flex flex-wrap items-center gap-3">
            <Button type="submit" size="lg" :disabled="form.processing">{{ submitLabel }}</Button>
            <!-- Other ways out of the form, e.g. Cancel. -->
            <slot name="actions" />
        </div>
    </form>
</template>
