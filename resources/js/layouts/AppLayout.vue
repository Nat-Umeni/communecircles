<script setup lang="ts">
import { ref } from 'vue';
import AppHeader from '@/components/AppHeader.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import VerifyEmailBanner from '@/components/account/VerifyEmailBanner.vue';
import Alert from '@/components/ui/Alert.vue';

// The drawer's open state, shared by the header's menu button and the sidebar.
const sidebarOpen = ref(false);
</script>

<template>
    <div class="min-h-dvh">
        <AppHeader v-model:sidebar-open="sidebarOpen">
            <template #actions>
                <slot name="actions" />
            </template>
        </AppHeader>

        <div class="flex">
            <AppSidebar v-model:open="sidebarOpen" />

            <main class="min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
                <div class="mx-auto flex max-w-[1200px] flex-col gap-6">
                    <VerifyEmailBanner />
                    <!-- One-off messages from Inertia::flash(), e.g. "Thanks, your email address is confirmed." -->
                    <Alert v-if="$page.flash.status">{{ $page.flash.status }}</Alert>
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
