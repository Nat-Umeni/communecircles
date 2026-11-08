<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import FormField from '@/components/ui/form/FormField.vue';
import Input from '@/components/ui/form/Input.vue';
import PasswordInput from '@/components/ui/form/PasswordInput.vue';

// Field names match the validation in app/Http/Controllers/Auth/RegisterController.php.
const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post(route('auth.register.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Join the Circle" />

        <div class="mx-auto flex w-full max-w-xl flex-col gap-6">
            <header class="flex flex-col gap-2 text-center">
                <h1 class="text-3xl font-extrabold sm:text-4xl">Join the Circle</h1>
                <p class="text-lg text-muted-foreground">Create an account to ask questions, share what you know and take part in the circles.</p>
            </header>

            <Card>
                <form class="flex flex-col gap-5" novalidate @submit.prevent="submit">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <FormField v-slot="field" label="First name" :error="form.errors.first_name">
                            <Input v-bind="field" v-model="form.first_name" name="first_name" autocomplete="given-name" required />
                        </FormField>

                        <FormField v-slot="field" label="Last name" :error="form.errors.last_name">
                            <Input v-bind="field" v-model="form.last_name" name="last_name" autocomplete="family-name" required />
                        </FormField>
                    </div>

                    <FormField v-slot="field" label="Email address" :error="form.errors.email">
                        <Input v-bind="field" v-model="form.email" type="email" name="email" autocomplete="email" placeholder="you@example.com" required />
                    </FormField>

                    <FormField v-slot="field" label="Password" hint="At least 8 characters, with a letter, a number and a symbol." :error="form.errors.password">
                        <PasswordInput v-bind="field" v-model="form.password" name="password" autocomplete="new-password" required />
                    </FormField>

                    <FormField v-slot="field" label="Confirm password" :error="form.errors.password_confirmation">
                        <PasswordInput v-bind="field" v-model="form.password_confirmation" name="password_confirmation" autocomplete="new-password" required />
                    </FormField>

                    <Button type="submit" size="lg" class="mt-2 w-full" :disabled="form.processing">
                        {{ form.processing ? 'Creating your account…' : 'Create account' }}
                    </Button>
                </form>
            </Card>

            <!-- Restore once a named login route exists (route() throws for unknown names), and import TextLink from @/components/ui/TextLink.vue:
            <p class="text-center text-muted-foreground">Already have an account? <TextLink :href="route('auth.login.create')">Log in</TextLink></p>
            -->
        </div>
    </AppLayout>
</template>
