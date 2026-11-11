<script setup lang="ts">
import Badge from '@/components/ui/Badge.vue';
import TimeAgo from '@/components/ui/TimeAgo.vue';
import Table from '@/components/ui/table/Table.vue';
import TableCell from '@/components/ui/table/TableCell.vue';
import TableHead from '@/components/ui/table/TableHead.vue';
import TableRow from '@/components/ui/table/TableRow.vue';
import type { Loaded, User } from '@/types';
import { fullName } from '@/utils/format';

// NOTE: the PaginatedList around it scrolls it, sideways too on a narrow phone; its header stays put as it scrolls.
defineProps<{
    /** In the order to show them: CircleController::show sends owners, then admins, then members. */
    members: Loaded<User, 'membership'>[];
}>();
</script>

<template>
    <Table>
        <template #head>
            <TableHead>Name</TableHead>
            <TableHead>Role</TableHead>
            <TableHead>Joined</TableHead>
        </template>
        <TableRow v-for="member in members" :key="member.membership.id">
            <!-- TODO: link to their profile once profiles exist. -->
            <TableCell class="font-semibold whitespace-nowrap">{{ fullName(member) }}</TableCell>
            <TableCell>
                <Badge variant="secondary">{{ member.membership.role.label }}</Badge>
            </TableCell>
            <TableCell class="text-sm whitespace-nowrap text-muted-foreground">
                <TimeAgo :datetime="member.membership.joined_at" />
            </TableCell>
        </TableRow>
    </Table>
</template>
