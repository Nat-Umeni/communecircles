<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import ApprovalActions from '@/components/approvals/ApprovalActions.vue';
import Card from '@/components/ui/Card.vue';
import Pagination from '@/components/ui/Pagination.vue';
import TimeAgo from '@/components/ui/TimeAgo.vue';
import type { NewCircleApproval, Paginated } from '@/types';

// From ApprovalController::index, for staff: the oldest first.
defineProps<{
    approvals: Paginated<NewCircleApproval>;
}>();
</script>

<template>
    <AppLayout>
        <Head title="Approvals" />

        <Card title="Approvals" description="New circles waiting for the team, oldest first. Once approved, a circle is listed for everyone." />

        <Card
            v-for="approval in approvals.data"
            :key="approval.id"
            :title="approval.circle.name"
            :href="route('circles.show', approval.circle.public_id)"
            :description="approval.circle.description"
            data-approval
        >
            <p class="text-sm text-muted-foreground">
                {{ approval.requester ? `Asked by ${approval.requester.first_name} ${approval.requester.last_name}` : 'Asked by a deleted account' }},
                <TimeAgo :datetime="approval.requested_at" />
            </p>
            <ApprovalActions :approval="approval.id" :subject="approval.circle.name" />
        </Card>
        <Card v-if="approvals.data.length === 0" title="Nothing is waiting for approval" description="New circles will show up here when they need the team." />

        <Pagination :paginator="approvals" />
    </AppLayout>
</template>
