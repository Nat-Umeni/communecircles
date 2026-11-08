<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import SidebarAccount from '@/components/account/SidebarAccount.vue';
import NavItem from '@/components/ui/NavItem.vue';
import Input from '@/components/ui/form/Input.vue';

// Whether the drawer is open on small screens. From lg up the sidebar is always shown.
const open = defineModel<boolean>('open', { required: true });
const search = ref('');

function closeOnEscape(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        open.value = false;
    }
}

onMounted(() => window.addEventListener('keydown', closeOnEscape));
onUnmounted(() => window.removeEventListener('keydown', closeOnEscape));

// NOTE: the root div is "contents" so the overlay and <aside> stay direct flex children of the layout, and lg:sticky still works.
</script>

<template>
    <div class="contents">
        <div v-if="open" class="fixed inset-0 top-16 z-30 bg-black/40 lg:hidden" aria-hidden="true" @click="open = false" />

        <aside
            id="app-sidebar"
            :class="[
                'fixed top-16 bottom-0 left-0 z-30 flex w-80 max-w-[85vw] shrink-0 flex-col gap-4 overflow-y-auto border-r border-border bg-surface p-4 transition-[translate,visibility] duration-200 *:shrink-0',
                'lg:sticky lg:top-20 lg:bottom-auto lg:visible lg:h-[calc(100dvh-5rem)] lg:translate-x-0',
                open ? 'translate-x-0' : 'invisible -translate-x-full',
            ]"
        >
            <Input v-model="search" type="search" placeholder="Search posts" aria-label="Search posts" />

            <!-- NOTE: each icon is the file of that name in public/images/icons. -->
            <!-- TODO: link each section once its page is done. -->
            <nav aria-label="Sections" class="flex flex-col gap-1">
                <NavItem :href="route('home')" icon="circles" :active="$page.component === 'Home'">Circles</NavItem>
                <NavItem icon="explore">Explore</NavItem>
                <NavItem icon="learn">Learn</NavItem>
                <NavItem icon="exchange">Exchange</NavItem>
                <NavItem icon="skills">Skills</NavItem>
                <NavItem icon="community">Community</NavItem>
                <NavItem icon="resources">Resources</NavItem>
                <!-- TODO: add once Seeds & Plants is done (its icon is already exported): <NavItem icon="seeds-plants">Seeds & Plants</NavItem> -->
            </nav>

            <div class="mt-auto flex flex-col gap-4 pt-4">
                <div class="sm:hidden">
                    <ThemeToggle />
                </div>
                <SidebarAccount />
            </div>
        </aside>
    </div>
</template>
