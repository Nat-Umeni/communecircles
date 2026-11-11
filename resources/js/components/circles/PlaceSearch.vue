<script setup lang="ts">
import { X } from '@lucide/vue';
import { computed, ref, useId, useTemplateRef } from 'vue';
import { usePlaceSearch } from '@/composables/usePlaceSearch';
import Input from '@/components/ui/form/Input.vue';
import type { Place } from '@/types';
import { placeLabel } from '@/utils/format';

// A FormField's id and aria attributes belong on the search box, not the wrapper.
defineOptions({ inheritAttrs: false });

// The chosen town or village, or null while there's none.
const place = defineModel<Place | null>({ required: true });

const { text, places, searching, failure, search } = usePlaceSearch(place.value ? placeLabel(place.value) : '');

const field = useTemplateRef<InstanceType<typeof Input>>('field');
const listId = useId();
const open = ref(false);
// The place picked out with the arrow keys, or -1 for none.
const highlighted = ref(-1);

const listShown = computed(() => open.value && places.value.length > 0);

const status = computed(() => {
    if (searching.value) {
        return 'Searching…';
    }

    if (failure.value) {
        return failure.value;
    }

    return open.value && places.value.length === 0 ? `No towns or villages match “${text.value}”.` : '';
});

// Typing unpicks the chosen place and closes the list until the new search is back.
async function typed(value: string): Promise<void> {
    if (place.value) {
        place.value = null;
    }

    open.value = false;

    if (await search(value)) {
        highlighted.value = -1;
        open.value = true;
    }
}

function choose(found: Place): void {
    place.value = found;
    text.value = placeLabel(found);
    open.value = false;
}

function clear(): void {
    place.value = null;
    text.value = '';
    field.value?.$el.focus();
}

function move(step: number): void {
    highlighted.value = Math.min(Math.max(highlighted.value + step, 0), places.value.length - 1);
}

// Enter picks the highlighted place; with none, it's left to submit the form.
function enter(event: KeyboardEvent): void {
    if (!listShown.value || highlighted.value < 0) {
        return;
    }

    event.preventDefault();
    choose(places.value[highlighted.value]);
}
</script>

<template>
    <div class="relative">
        <Input
            ref="field"
            v-bind="$attrs"
            :model-value="text"
            type="text"
            role="combobox"
            autocomplete="off"
            aria-autocomplete="list"
            :aria-expanded="listShown"
            :aria-controls="listId"
            :aria-activedescendant="listShown && highlighted >= 0 ? `${listId}-${highlighted}` : undefined"
            class="pr-12"
            @update:model-value="typed"
            @keydown.down.prevent="move(1)"
            @keydown.up.prevent="move(-1)"
            @keydown.enter="enter"
            @keydown.esc="open = false"
            @focusout="open = false"
        />
        <button
            v-if="place"
            type="button"
            aria-label="Clear the place"
            class="absolute top-1.5 right-1.5 grid size-9 cursor-pointer place-items-center rounded-control text-muted-foreground transition-colors hover:bg-secondary focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            @click="clear"
        >
            <X class="size-5" aria-hidden="true" />
        </button>
        <ul
            v-if="listShown"
            :id="listId"
            role="listbox"
            class="absolute z-10 mt-1 max-h-64 w-full overflow-auto rounded-control border border-border bg-surface py-1 shadow-lg"
        >
            <!-- NOTE: mousedown is prevented so clicking a place doesn't take focus from the search box first. -->
            <li
                v-for="(found, index) in places"
                :id="`${listId}-${index}`"
                :key="found.geonames_id"
                role="option"
                :aria-selected="index === highlighted"
                class="cursor-pointer px-4 py-2 text-foreground hover:bg-secondary aria-selected:bg-secondary"
                @mousedown.prevent
                @click="choose(found)"
            >
                {{ placeLabel(found) }}
            </li>
        </ul>
        <p role="status" class="mt-2 text-sm empty:hidden" :class="failure ? 'font-semibold text-danger' : 'text-muted-foreground'">{{ status }}</p>
    </div>
</template>
