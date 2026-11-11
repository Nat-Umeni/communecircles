<script setup lang="ts">
import { X } from '@lucide/vue';
import { useTemplateRef } from 'vue';
import Input from '@/components/ui/form/Input.vue';

// The box's own attributes (name, aria-label, placeholder) belong on the input, not the wrapper.
defineOptions({ inheritAttrs: false });

const text = defineModel<string>({ required: true });

// Told after the ✕ empties the box, so the page can search for everything again straight away.
const emit = defineEmits<{ clear: [] }>();

const field = useTemplateRef<InstanceType<typeof Input>>('field');

function clear(): void {
    text.value = '';
    field.value!.$el.focus();
    emit('clear');
}
</script>

<template>
    <div class="relative w-full">
        <!-- NOTE: the browser's own clear button (Chrome, Safari) is hidden so there's only this one. -->
        <Input ref="field" v-bind="$attrs" v-model="text" type="search" class="pr-12 [&::-webkit-search-cancel-button]:appearance-none" />
        <button
            v-if="text"
            type="button"
            aria-label="Clear the search"
            class="absolute top-1.5 right-1.5 grid size-9 cursor-pointer place-items-center rounded-control text-muted-foreground transition-colors hover:bg-secondary focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            @click="clear"
        >
            <X class="size-5" aria-hidden="true" />
        </button>
    </div>
</template>
