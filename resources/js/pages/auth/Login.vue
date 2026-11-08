<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useTooManyAttempts } from '@/composables/useTooManyAttempts';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import TextLink from '@/components/ui/TextLink.vue';
import Checkbox from '@/components/ui/form/Checkbox.vue';
import FormField from '@/components/ui/form/FormField.vue';
import Input from '@/components/ui/form/Input.vue';
import PasswordInput from '@/components/ui/form/PasswordInput.vue';

// Field names match the validation in app/Http/Controllers/Auth/SessionController.php.
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit(): void {
    form.post(route('login.store'), {
        onFinish: () => form.reset('password'),
        onHttpException: useTooManyAttempts((message) => form.setError('email', message)),
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Log in" />

        <div class="mx-auto flex w-full max-w-xl flex-col gap-6">
            <header class="flex flex-col gap-2 text-center">
                <h1 class="text-3xl font-extrabold sm:text-4xl">Welcome back</h1>
                <p class="text-lg text-muted-foreground">Log in to pick up where you left off in the circles.</p>
            </header>

            <Card>
                <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
                    <FormField v-slot="field" label="Email address" :error="form.errors.email">
                        <Input v-bind="field" v-model="form.email" type="email" name="email" autocomplete="email" placeholder="you@example.com" required />
                    </FormField>

                    <FormField v-slot="field" label="Password" :error="form.errors.password">
                        <PasswordInput v-bind="field" v-model="form.password" name="password" autocomplete="current-password" required />
                    </FormField>

                    <!-- TODO: add a "Forgot your password?" link once password resets are done. -->
                    <label class="flex cursor-pointer items-center gap-3 self-start">
                        <Checkbox v-model="form.remember" name="remember" />
                        Remember me
                    </label>

                    <Button type="submit" size="lg" class="mt-2 w-full" :disabled="form.processing">
                        {{ form.processing ? 'Logging in…' : 'Log in' }}
                    </Button>
                </form>
            </Card>

            <p class="text-center text-muted-foreground">New here? <TextLink :href="route('auth.register.create')">Join the Circle</TextLink></p>
        </div>
    </AppLayout>
</template>
