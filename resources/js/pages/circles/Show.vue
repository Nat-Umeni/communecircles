<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Mail, Pencil } from '@lucide/vue';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import HeaderAction from '@/components/HeaderAction.vue';
import ApprovalActions from '@/components/approvals/ApprovalActions.vue';
import CancelInvite from '@/components/circles/CancelInvite.vue';
import CircleLocation from '@/components/circles/CircleLocation.vue';
import DeleteCircle from '@/components/circles/DeleteCircle.vue';
import InviteDialog from '@/components/circles/InviteDialog.vue';
import MembersTable from '@/components/circles/MembersTable.vue';
import Alert from '@/components/ui/Alert.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import CountPill from '@/components/ui/CountPill.vue';
import TimeAgo from '@/components/ui/TimeAgo.vue';
import MenuItem from '@/components/ui/menu/MenuItem.vue';
import type { Approval, Circle, CirclePermissions, Loaded, User } from '@/types';
import { fullName } from '@/utils/format';

// From CircleController::show.
const props = defineProps<{
    circle: Loaded<Circle, 'members_count'>;
    can: CirclePermissions;
    /** The circle's own approval, while it isn't approved; null once it is. */
    approval: Approval | null;
    /** The viewer's own request to join, if they've asked. */
    join_request: Approval | null;
    /** For the circle's owners and admins only; null for everyone else. */
    pending_join_requests: Loaded<Approval, 'user'>[] | null;
    /** The viewer's own invite: waiting for their answer, or expired. */
    invite: Loaded<Approval, 'requester'> | null;
    /** For the circle's owners and admins only; null for everyone else. */
    pending_invites: Loaded<Approval, 'user'>[] | null;
    /** Everyone in the circle, for its owners, admins and staff; null for everyone else, who get only the count. */
    members: Loaded<User, 'membership'>[] | null;
}>();

const joining = useForm({});

// Whether the invite dialog is open; the header's Create menu opens it.
const inviting = ref(false);

function askToJoin(): void {
    joining.post(route('circles.join-requests.store', props.circle.public_id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <Head :title="circle.name" />

        <template #actions>
            <HeaderAction v-if="can.update" :href="route('circles.edit', circle.public_id)" label="Edit circle">
                <Pencil class="size-5" aria-hidden="true" />
            </HeaderAction>
        </template>

        <template #create>
            <MenuItem v-if="can.invite" @click="inviting = true">
                <Mail class="size-4" aria-hidden="true" />
                Invite someone
            </MenuItem>
        </template>

        <InviteDialog v-if="can.invite" v-model:open="inviting" :circle="circle.public_id" />

        <Alert v-if="approval" variant="warning" data-approval>
            <p class="font-semibold">{{ approval.status.label }}</p>
            <p v-if="approval.status.value === 'pending'">The team checks each new circle before it's listed. Until then, only its members and the team can see it.</p>
            <p v-if="approval.reason">“{{ approval.reason }}”</p>
            <p v-if="approval.status.value === 'rejected' && can.update">Edit the circle to send it to the team again.</p>
            <ApprovalActions v-if="approval.can.decide" :approval="approval.id" :subject="circle.name" class="mt-3" />
        </Alert>

        <Card v-if="invite?.status.value === 'expired'" title="This invite has expired" />
        <Card
            v-else-if="invite"
            title="You're invited"
            :description="invite.requester ? `${fullName(invite.requester)} invited you to join this circle.` : `You've been invited to join this circle.`"
        >
            <ApprovalActions :approval="invite.id" subject="the invite" approve-label="Accept" reject-label="Decline" />
        </Card>

        <Card :title="circle.name" :description="circle.description">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <CircleLocation v-if="circle.location" :location="circle.location" />
                <CountPill :count="circle.members_count" singular="member" plural="members" />
            </div>

            <div v-if="join_request" data-join-request class="flex flex-col gap-1">
                <p>Your request to join: <strong>{{ join_request.status.label }}</strong></p>
                <p v-if="join_request.reason" class="text-muted-foreground">“{{ join_request.reason }}”</p>
            </div>

            <div class="flex flex-wrap items-center gap-3 empty:hidden">
                <Button v-if="can.request_to_join" :disabled="joining.processing" @click="askToJoin">Ask to join</Button>
                <Button v-else-if="!$page.props.auth.user" :href="route('login')">Log in to ask to join</Button>
                <DeleteCircle v-if="can.delete" :circle="circle" />
            </div>
        </Card>

        <!-- NOTE: stacked on phones; side by side from sm up, and one card alone takes the whole row. -->
        <div class="grid gap-6 empty:hidden sm:auto-cols-fr sm:grid-flow-col">
            <Card v-if="pending_join_requests" title="Requests to join" data-join-requests>
                <ul v-if="pending_join_requests.length" class="flex flex-col divide-y divide-border">
                    <li v-for="request in pending_join_requests" :key="request.id" class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <p>
                            <span class="font-semibold">{{ fullName(request.user) }}</span>
                            <span class="text-sm text-muted-foreground"> asked <TimeAgo :datetime="request.requested_at" /></span>
                        </p>
                        <!-- TODO: link to their profile once profiles exist, so they can be checked first. -->
                        <ApprovalActions v-if="request.can.decide" :approval="request.id" :subject="`${fullName(request.user)}'s request`" approve-label="Accept" reject-label="Decline" />
                    </li>
                </ul>
                <p v-else class="text-muted-foreground">No one is waiting to join.</p>
            </Card>

            <Card v-if="pending_invites" title="Invites" data-invites>
                <ul v-if="pending_invites.length" class="flex flex-col divide-y divide-border">
                    <li v-for="invited in pending_invites" :key="invited.id" class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <p>
                            <span class="font-semibold">{{ fullName(invited.user) }}</span>
                            <span class="text-sm text-muted-foreground"> invited <TimeAgo :datetime="invited.requested_at" /></span>
                        </p>
                        <CancelInvite v-if="invited.can.cancel" :invite="invited.id" :name="fullName(invited.user)" />
                    </li>
                </ul>
                <p v-else class="text-muted-foreground">No invites are waiting for an answer.</p>
            </Card>
        </div>

        <Card v-if="members" title="Members" data-members>
            <MembersTable :members="members" />
        </Card>

        <!-- TODO: list the circle's channels once they exist. -->
        <Card v-if="can.view_channels" title="Channels" description="Conversations in this circle will be here soon." data-channels />
    </AppLayout>
</template>
