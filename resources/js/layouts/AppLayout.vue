<script setup>
import { ref } from 'vue';
import AppHeader from '@/components/AppHeader.vue';
import AppSidebar from '@/components/AppSidebar.vue';

// The drawer's open state, shared by the header's menu button and the sidebar.
const sidebarOpen = ref(false);
</script>

<template>
    <div class="min-h-dvh">
        <AppHeader v-model:sidebar-open="sidebarOpen" />

        <div class="flex">
            <AppSidebar v-model:open="sidebarOpen" />

            <main class="min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
                <div class="mx-auto flex max-w-[1200px] flex-col gap-6">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
