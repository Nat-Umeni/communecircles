<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useTooManyAttempts } from '@/composables/useTooManyAttempts';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import TextLink from '@/components/ui/TextLink.vue';
import FormField from '@/components/ui/form/FormField.vue';
import Input from '@/components/ui/form/Input.vue';

// Field names match the validation in app/Http/Controllers/Auth/PasswordResetController.php.
const form = useForm({
    email: '',
});

function submit(): void {
    form.post(route('password.email'), {
        onHttpException: useTooManyAttempts((message) => form.setError('email', message)),
    });
}
</script>

<template>
    <AuthLayout title="Forgot your password?" description="Enter your email address and we'll send you a link to choose a new one.">
        <Head title="Forgot password" />

        <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
            <FormField v-slot="field" label="Email address" :error="form.errors.email">
                <Input v-bind="field" v-model="form.email" type="email" name="email" autocomplete="email" placeholder="you@example.com" required />
            </FormField>

            <Button type="submit" size="lg" class="mt-2 w-full" :disabled="form.processing">
                {{ form.processing ? 'Sending…' : 'Email me a reset link' }}
            </Button>
        </form>

        <template #footer>Remembered it? <TextLink :href="route('login')">Log in</TextLink></template>
    </AuthLayout>
</template>
