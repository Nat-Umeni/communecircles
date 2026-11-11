<script setup lang="ts">
import { useId } from 'vue';
import { route } from 'ziggy-js';
import { useFormDialog } from '@/composables/ui/useFormDialog';
import Button from '@/components/ui/Button.vue';
import Dialog from '@/components/ui/Dialog.vue';
import FormField from '@/components/ui/form/FormField.vue';
import Input from '@/components/ui/form/Input.vue';

const props = defineProps<{
    /** The public_id of the circle to invite them to. */
    circle: string;
}>();

// The page opens it (from the header's Create menu); closing it here, or sending the invite, goes back through v-model:open.
const open = defineModel<boolean>('open', { required: true });

// Field names match the validation in app/Http/Requests/Circle/StoreInviteRequest.php.
const { form, send } = useFormDialog({ email: '' }, open);
const formId = useId();

function submit(): void {
    send('post', route('circles.invites.store', props.circle), { preserveScroll: true });
}
</script>

<template>
    <Dialog v-model:open="open" title="Invite someone">
        <form :id="formId" novalidate @submit.prevent="submit">
            <FormField v-slot="field" label="Email address" hint="They'll need an account already." :error="form.errors.email">
                <Input v-bind="field" v-model="form.email" type="email" name="email" autocomplete="off" placeholder="them@example.com" required />
            </FormField>
        </form>
        <template #actions>
            <Button variant="surface" @click="open = false">Cancel</Button>
            <Button type="submit" :form="formId" :disabled="form.processing">Send invite</Button>
        </template>
    </Dialog>
</template>
