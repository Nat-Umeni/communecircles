<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Button from '@/components/ui/Button.vue';
import FormField from '@/components/ui/form/FormField.vue';
import Input from '@/components/ui/form/Input.vue';

const props = defineProps<{
    /** The public_id of the circle to invite them to. */
    circle: string;
}>();

// Field names match the validation in app/Http/Requests/Circle/StoreInviteRequest.php.
const form = useForm({ email: '' });

// NOTE: the field and button sit side by side by the form's own width, not the screen's, since the form can be in half a row.

function submit(): void {
    form.post(route('circles.invites.store', props.circle), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <form class="@container" novalidate @submit.prevent="submit">
        <div class="flex flex-col gap-3 @md:flex-row @md:items-start">
            <FormField v-slot="field" label="Email address" hint="They'll need an account already." :error="form.errors.email" class="flex-1">
                <Input v-bind="field" v-model="form.email" type="email" name="email" autocomplete="off" placeholder="them@example.com" required />
            </FormField>
            <Button type="submit" size="lg" class="@md:mt-7" :disabled="form.processing">Send invite</Button>
        </div>
    </form>
</template>
