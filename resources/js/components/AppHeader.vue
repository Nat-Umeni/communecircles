<script setup>
import { Menu, X } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';

// Whether the sidebar drawer is open on small screens (shared with AppSidebar through AppLayout).
const sidebarOpen = defineModel('sidebarOpen', { type: Boolean, required: true });
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

        <!-- $page.props.homeUrl is shared with every page by app/Http/Middleware/HandleInertiaRequests.php. -->
        <AppLogo :href="$page.props.homeUrl" />

        <div class="ml-auto hidden sm:block">
            <ThemeToggle tone="brand" />
        </div>
    </header>
</template>
