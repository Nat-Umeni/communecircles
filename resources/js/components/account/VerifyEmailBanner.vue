<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Alert from '@/components/ui/Alert.vue';
import TextLink from '@/components/ui/TextLink.vue';

const page = usePage();

// Shared with every page by app/Http/Middleware/HandleInertiaRequests.php; null for guests.
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Alert v-if="user && !user.email_verified" variant="warning">
        Please confirm your email address. We sent a link to <strong class="font-semibold">{{ user.email }}</strong>.
        <TextLink :href="route('verification.send')" method="post">Send it again</TextLink>
    </Alert>
</template>
