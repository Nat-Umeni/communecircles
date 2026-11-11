<script setup lang="ts">
import { LogOut, Settings, UserRound } from '@lucide/vue';
import { useAuth } from '@/composables/auth/useAuth';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import DropdownMenu from '@/components/ui/menu/DropdownMenu.vue';
import MenuItem from '@/components/ui/menu/MenuItem.vue';
import type { Role } from '@/types';
import { fullName } from '@/utils/format';

const { user } = useAuth();

// Staff get a badge (with the label from PHP) under their email; ordinary members don't.
const badgeVariants: Partial<Record<Role, 'accent' | 'primary'>> = {
    admin: 'accent',
    super_admin: 'primary',
};

</script>

<template>
    <div v-if="user" class="flex items-center gap-3 rounded-card border border-border bg-background py-2 pr-2 pl-3">
        <!-- TODO: show the member's photo once photo uploads are done. -->
        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-highlight text-highlight-foreground" aria-hidden="true">
            <UserRound class="size-5" />
        </span>
        <div class="flex min-w-0 flex-1 flex-col">
            <span class="truncate font-heading font-bold text-heading">{{ fullName(user) }}</span>
            <span class="truncate text-sm text-muted-foreground">{{ user.email }}</span>
            <Badge v-if="badgeVariants[user.role.value]" :variant="badgeVariants[user.role.value]" class="mt-1 self-start">{{ user.role.label }}</Badge>
        </div>
        <DropdownMenu label="Account menu" side="top">
            <template #trigger>
                <Settings class="size-5" aria-hidden="true" />
            </template>
            <!-- TODO: add a Settings item once the settings page is done. -->
            <MenuItem :href="route('logout')" method="post">
                <LogOut class="size-4" aria-hidden="true" />
                Log out
            </MenuItem>
        </DropdownMenu>
    </div>

    <div v-else class="flex flex-col gap-3 rounded-card border border-border bg-background p-4">
        <p class="text-sm text-muted-foreground">Join to ask questions, share what you know and take part in the circles.</p>
        <div class="grid grid-cols-2 gap-2">
            <Button variant="surface" :href="route('login')">Log in</Button>
            <Button :href="route('auth.register.create')">Register</Button>
        </div>
    </div>
</template>
