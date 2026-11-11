<script setup lang="ts">
import { route } from 'ziggy-js';
import { useFormDialog } from '@/composables/ui/useFormDialog';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import type { Circle } from '@/types';

const props = defineProps<{
    circle: Pick<Circle, 'public_id' | 'name'>;
}>();

const { open: confirming, form: deleting, send } = useFormDialog({});

function destroy(): void {
    send('delete', route('circles.destroy', props.circle.public_id));
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
