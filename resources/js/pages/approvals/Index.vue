<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ApprovalActions from '@/components/approvals/ApprovalActions.vue';
import Badge from '@/components/ui/Badge.vue';
import Card from '@/components/ui/Card.vue';
import PaginatedList from '@/components/ui/pagination/PaginatedList.vue';
import TimeAgo from '@/components/ui/TimeAgo.vue';
import type { Approval, Loaded, Paginated } from '@/types';
import { fullName } from '@/utils/format';

// From ApprovalController::index, for staff: everything waiting for their approval, the longest-waiting first.
defineProps<{
    approvals: Paginated<Loaded<Approval, 'subject' | 'requester'>>;
}>();
</script>

<template>
    <AppLayout>
        <Head title="Approvals" />

        <Card title="Approvals" description="Everything waiting for the team's approval, the longest-waiting first." />

        <PaginatedList v-if="approvals.data.length" :paginator="approvals" label="Approvals" :only="['approvals']" size="page">
            <div class="flex flex-col gap-6">
                <Card
                    v-for="approval in approvals.data"
                    :key="approval.id"
                    :title="approval.subject.name"
                    :href="approval.subject.url"
                    :description="approval.subject.description"
                    data-approval
                >
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                        <Badge variant="secondary" data-type>{{ approval.type.label }}</Badge>
                        <p class="text-sm text-muted-foreground">
                            {{ approval.requester ? `Asked by ${fullName(approval.requester)}` : 'Asked by a deleted account' }},
                            <TimeAgo :datetime="approval.requested_at" />
                        </p>
                    </div>
                    <ApprovalActions :approval="approval.id" :subject="approval.subject.name" />
                </Card>
            </div>
        </PaginatedList>
        <Card v-else title="Nothing is waiting for approval" description="Anything that needs the team will show up here." />
    </AppLayout>
</template>
