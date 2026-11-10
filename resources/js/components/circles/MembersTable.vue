<script setup lang="ts">
import Badge from '@/components/ui/Badge.vue';
import TimeAgo from '@/components/ui/TimeAgo.vue';
import type { CircleMember } from '@/types';

// NOTE: the table scrolls sideways on its own when a phone is too narrow for it, so the page doesn't.
defineProps<{
    /** In the order to show them: CircleController::show sends owners, then admins, then members. */
    members: CircleMember[];
}>();
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="text-sm text-muted-foreground">
                <tr class="border-b border-border">
                    <th scope="col" class="py-2 pr-4 font-semibold">Name</th>
                    <th scope="col" class="py-2 pr-4 font-semibold">Role</th>
                    <th scope="col" class="py-2 font-semibold">Joined</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                <tr v-for="member in members" :key="member.id">
                    <!-- TODO: link to their profile once profiles exist. -->
                    <td class="py-3 pr-4 font-semibold whitespace-nowrap">{{ member.name }}</td>
                    <td class="py-3 pr-4">
                        <Badge variant="secondary">{{ member.role.label }}</Badge>
                    </td>
                    <td class="py-3 text-sm whitespace-nowrap text-muted-foreground">
                        <TimeAgo :datetime="member.joined_at" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
