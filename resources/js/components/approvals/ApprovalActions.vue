<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useId } from 'vue';
import { route } from 'ziggy-js';
import { useFormDialog } from '@/composables/ui/useFormDialog';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import FormField from '@/components/ui/form/FormField.vue';
import Textarea from '@/components/ui/form/Textarea.vue';

const props = withDefaults(
    defineProps<{
        /** The id of the approval to decide. */
        approval: number;
        /** The thing waiting for approval, as the reject dialog asks about it, e.g. "Seed Exchange" or "the invite". */
        subject: string;
        approveLabel?: string;
        rejectLabel?: string;
    }>(),
    { approveLabel: 'Approve', rejectLabel: 'Reject' },
);

const approving = useForm({});
const { open: asking, form: rejecting, send } = useFormDialog({ reason: '' });
const reasonFormId = useId();

function approve(): void {
    approving.post(route('approvals.approve', props.approval), { preserveScroll: true });
}

function reject(): void {
    send('post', route('approvals.reject', props.approval), { preserveScroll: true });
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-3">
        <Button size="sm" :disabled="approving.processing || rejecting.processing" @click="approve">{{ approveLabel }}</Button>
        <Button variant="surface" size="sm" :disabled="approving.processing || rejecting.processing" @click="asking = true">{{ rejectLabel }}</Button>

        <Dialog v-model:open="asking" :title="`${rejectLabel} ${subject}?`">
            <form :id="reasonFormId" novalidate @submit.prevent="reject">
                <FormField v-slot="field" label="Reason (optional)" hint="They'll see this." :error="rejecting.errors.reason">
                    <Textarea v-bind="field" v-model="rejecting.reason" name="reason" maxlength="500" />
                </FormField>
            </form>
            <template #actions>
                <Button variant="surface" @click="asking = false">Cancel</Button>
                <Button type="submit" :form="reasonFormId" :disabled="rejecting.processing">{{ rejectLabel }}</Button>
            </template>
        </Dialog>
    </div>
</template>
