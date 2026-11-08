<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { LogOut, Settings, UserRound } from '@lucide/vue';
import { computed } from 'vue';
import Button from '@/components/ui/Button.vue';
import DropdownMenu from '@/components/ui/menu/DropdownMenu.vue';
import MenuItem from '@/components/ui/menu/MenuItem.vue';

const page = usePage();

// Shared with every page by app/Http/Middleware/HandleInertiaRequests.php; null for guests.
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div v-if="user" class="flex items-center gap-3 rounded-card border border-border bg-background py-2 pr-2 pl-3">
        <!-- TODO: show the member's photo once photo uploads are done. -->
        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-highlight text-highlight-foreground" aria-hidden="true">
            <UserRound class="size-5" />
        </span>
        <div class="flex min-w-0 flex-1 flex-col">
            <span class="truncate font-heading font-bold text-heading">{{ user.first_name }} {{ user.last_name }}</span>
            <span class="truncate text-sm text-muted-foreground">{{ user.email }}</span>
        </div>
        <DropdownMenu label="Account menu" side="top">
            <template #trigger>
                <Settings class="size-5" aria-hidden="true" />
            </template>
            <!-- TODO: add a Settings item once the settings page is done. -->
            <MenuItem :href="route('auth.logout')" method="post">
                <LogOut class="size-4" aria-hidden="true" />
                Log out
            </MenuItem>
        </DropdownMenu>
    </div>

    <div v-else class="flex flex-col gap-3 rounded-card border border-border bg-background p-4">
        <p class="text-sm text-muted-foreground">Join to ask questions, share what you know and take part in the circles.</p>
        <div class="grid grid-cols-2 gap-2">
            <!-- TODO: link to the login page once it's done. -->
            <Button variant="surface">Log in</Button>
            <Button :href="route('auth.register.create')">Register</Button>
        </div>
    </div>
</template>
