<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import CircleForm from '@/components/circles/CircleForm.vue';
import Alert from '@/components/ui/Alert.vue';
import Card from '@/components/ui/Card.vue';
import type { Option } from '@/types';

// From CircleController::create.
defineProps<{
    /** Why the member can't start a circle yet (CirclePolicy::create), or null when they can. */
    cannot_create_reason: string | null;
    countries: Option[];
}>();
</script>

<template>
    <AppLayout>
        <Head title="Start a circle" />

        <Card title="Start a circle" description="The team checks each new circle before it is listed.">
            <Alert v-if="cannot_create_reason" variant="warning">{{ cannot_create_reason }}</Alert>
            <CircleForm v-else method="post" :url="route('circles.store')" :countries="countries" submit-label="Start the circle" />
        </Card>
    </AppLayout>
</template>
