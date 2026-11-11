<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import { useAuth } from '@/composables/auth/useAuth';
import { useTooManyAttempts } from '@/composables/auth/useTooManyAttempts';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import TextLink from '@/components/ui/TextLink.vue';
import InputError from '@/components/ui/form/InputError.vue';

const { user } = useAuth();
const form = useForm({});
const tooManyAttempts = ref<string>();

function resend(): void {
    tooManyAttempts.value = undefined;
    form.post(route('verification.send'), {
        onHttpException: useTooManyAttempts((message) => (tooManyAttempts.value = message)),
    });
}
</script>

<template>
    <AuthLayout title="Check your inbox" description="One last step: confirm your email address.">
        <Head title="Confirm your email" />

        <div class="flex flex-col gap-5">
            <p>
                We've sent a link to <strong class="font-semibold text-heading">{{ user?.email }}</strong>. Click it to confirm your
                email address and finish setting up your account.
            </p>
            <p class="text-sm text-muted-foreground">Can't find it? Check your spam folder, or we can send it again.</p>

            <Button size="lg" class="w-full" :disabled="form.processing" @click="resend">
                {{ form.processing ? 'Sending…' : 'Send the email again' }}
            </Button>
            <InputError :message="tooManyAttempts" />
        </div>

        <template #footer>Wrong email address? <TextLink :href="route('logout')" method="post">Log out</TextLink> and register again.</template>
    </AuthLayout>
</template>
