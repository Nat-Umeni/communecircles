<script setup lang="ts">
import { route } from 'ziggy-js';
import { useFormDialog } from '@/composables/ui/useFormDialog';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';

// NOTE: the person invited is only told their invite expired (CircleController::inviteFor), never that it was cancelled.
const props = defineProps<{
    /** The id of the invite (its approval) to cancel. */
    invite: number;
    /** Who was invited. */
    name: string;
}>();

const { open: confirming, form: cancelling, send } = useFormDialog({});

function cancel(): void {
    send('post', route('approvals.cancel', props.invite), { preserveScroll: true });
}
</script>

<template>
    <div>
        <Button variant="surface" size="sm" :aria-label="`Cancel ${name}'s invite`" @click="confirming = true">Cancel</Button>

        <Dialog v-model:open="confirming" :title="`Cancel ${name}'s invite?`">
            <p>They won't be able to accept it. You can invite them again later.</p>
            <template #actions>
                <Button variant="surface" @click="confirming = false">Keep it</Button>
                <Button variant="danger" :disabled="cancelling.processing" @click="cancel">Cancel invite</Button>
            </template>
        </Dialog>
    </div>
</template>
