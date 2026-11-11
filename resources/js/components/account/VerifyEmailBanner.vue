<script setup lang="ts">
import { useAuth } from '@/composables/auth/useAuth';
import Alert from '@/components/ui/Alert.vue';
import TextLink from '@/components/ui/TextLink.vue';

const { user } = useAuth();
</script>

<template>
    <Alert v-if="user && !user.email_verified" variant="warning">
        Please confirm your email address. We sent a link to <strong class="font-semibold">{{ user.email }}</strong>.
        <TextLink :href="route('verification.send')" method="post">Send it again</TextLink>
    </Alert>
</template>
