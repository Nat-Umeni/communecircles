<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useTooManyAttempts } from '@/composables/auth/useTooManyAttempts';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import TextLink from '@/components/ui/TextLink.vue';
import FormField from '@/components/ui/form/FormField.vue';
import Input from '@/components/ui/form/Input.vue';
import PasswordInput from '@/components/ui/form/PasswordInput.vue';

// From app/Http/Controllers/Auth/PasswordResetController.php::edit(), taken from the link in the reset email.
const props = defineProps<{
    token: string;
    email: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onHttpException: useTooManyAttempts((message) => form.setError('email', message)),
    });
}
</script>

<template>
    <AuthLayout title="Choose a new password" description="Almost there. Pick a new password for your account.">
        <Head title="Reset password" />

        <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
            <!-- NOTE: an invalid or expired link is reported on the email field. -->
            <FormField v-slot="field" label="Email address" :error="form.errors.email ?? form.errors.token">
                <Input v-bind="field" v-model="form.email" type="email" name="email" autocomplete="email" required />
            </FormField>

            <FormField v-slot="field" label="New password" hint="At least 8 characters, with a letter, a number and a symbol." :error="form.errors.password">
                <PasswordInput v-bind="field" v-model="form.password" name="password" autocomplete="new-password" required />
            </FormField>

            <FormField v-slot="field" label="Confirm new password" :error="form.errors.password_confirmation">
                <PasswordInput v-bind="field" v-model="form.password_confirmation" name="password_confirmation" autocomplete="new-password" required />
            </FormField>

            <Button type="submit" size="lg" class="mt-2 w-full" :disabled="form.processing">
                {{ form.processing ? 'Saving…' : 'Set new password' }}
            </Button>
        </form>

        <template #footer>Link not working? <TextLink :href="route('password.request')">Ask for a new one</TextLink></template>
    </AuthLayout>
</template>
