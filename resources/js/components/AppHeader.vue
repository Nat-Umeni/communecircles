<script setup lang="ts">
import { CirclePlus, Menu, Plus, X } from '@lucide/vue';
import { useAuth } from '@/composables/auth/useAuth';
import AppLogo from '@/components/AppLogo.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import DropdownMenu from '@/components/ui/menu/DropdownMenu.vue';
import MenuItem from '@/components/ui/menu/MenuItem.vue';

// Whether the sidebar drawer is open on small screens (shared with AppSidebar through AppLayout).
const sidebarOpen = defineModel<boolean>('sidebarOpen', { required: true });

const { signedIn } = useAuth();
</script>

<template>
    <header class="sticky top-0 z-40 flex h-16 items-center gap-3 bg-brand px-4 text-brand-foreground lg:h-20 lg:px-6">
        <button
            type="button"
            class="-ml-1 grid size-10 place-items-center rounded-control transition-colors hover:bg-brand-foreground/10 focus-visible:ring-2 focus-visible:ring-brand-foreground focus-visible:outline-none lg:hidden"
            aria-controls="app-sidebar"
            :aria-expanded="sidebarOpen"
            @click="sidebarOpen = !sidebarOpen"
        >
            <component :is="sidebarOpen ? X : Menu" class="size-6" aria-hidden="true" />
            <span class="sr-only">{{ sidebarOpen ? 'Close menu' : 'Open menu' }}</span>
        </button>

        <AppLogo :href="route('home')" />

        <div class="ml-auto flex items-center gap-3">
            <!-- The page's own actions, e.g. editing the circle being viewed. -->
            <slot name="actions" />

            <!-- Only members can create things. -->
            <DropdownMenu v-if="signedIn" label="Create" tone="brand">
                <template #trigger>
                    <Plus class="size-5" aria-hidden="true" />
                </template>
                <MenuItem :href="route('circles.create')">
                    <CirclePlus class="size-4" aria-hidden="true" />
                    Create a circle
                </MenuItem>
                <!-- The page's own MenuItems, e.g. inviting someone into the circle being viewed. -->
                <slot name="create" />
            </DropdownMenu>

            <div class="hidden sm:block">
                <ThemeToggle tone="brand" />
            </div>
        </div>
    </header>
</template>
