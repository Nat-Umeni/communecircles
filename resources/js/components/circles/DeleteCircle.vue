<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import type { Circle } from '@/types';

const props = defineProps<{
    circle: Pick<Circle, 'public_id' | 'name'>;
}>();

const deleting = useForm({});
const confirming = ref(false);

function destroy(): void {
    deleting.delete(route('circles.destroy', props.circle.public_id));
}
</script>

<template>
    <div>
        <Button variant="surface" @click="confirming = true">Delete</Button>

        <Dialog v-model:open="confirming" :title="`Delete ${circle.name}?`">
            <p>This can't be undone. The circle and everything in it will be gone for its members too.</p>
            <template #actions>
                <Button variant="surface" @click="confirming = false">Cancel</Button>
                <Button variant="danger" :disabled="deleting.processing" @click="destroy">Delete the circle</Button>
            </template>
        </Dialog>
    </div>
</template>
