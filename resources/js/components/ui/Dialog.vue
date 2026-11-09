<script setup lang="ts">
import { X } from '@lucide/vue';
import { onMounted, ref, useId, watch } from 'vue';

defineProps<{
    title: string;
}>();

// Open and close it with v-model:open; it reports closing itself (close button, Escape) back through it.
const open = defineModel<boolean>('open', { required: true });

const dialog = ref<HTMLDialogElement | null>(null);
const titleId = useId();

function sync(isOpen: boolean): void {
    if (isOpen && !dialog.value!.open) {
        dialog.value!.showModal();
    } else if (!isOpen && dialog.value!.open) {
        dialog.value!.close();
    }
}

onMounted(() => sync(open.value));
watch(open, sync);
</script>

<template>
    <dialog
        ref="dialog"
        :aria-labelledby="titleId"
        class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-card border border-border bg-surface p-6 text-foreground shadow-lg backdrop:bg-black/50"
        @close="open = false"
    >
        <header class="flex items-start justify-between gap-4">
            <h2 :id="titleId" class="text-xl font-bold">{{ title }}</h2>
            <button
                type="button"
                aria-label="Close"
                class="-m-1 grid size-9 shrink-0 cursor-pointer place-items-center rounded-control text-muted-foreground transition-colors hover:bg-secondary focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                @click="open = false"
            >
                <X class="size-5" aria-hidden="true" />
            </button>
        </header>
        <div class="mt-3 flex flex-col gap-4">
            <slot />
        </div>
        <footer v-if="$slots.actions" class="mt-6 flex flex-wrap justify-end gap-3">
            <slot name="actions" />
        </footer>
    </dialog>
</template>
