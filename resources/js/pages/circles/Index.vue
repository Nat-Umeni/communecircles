<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import CircleCard from '@/components/circles/CircleCard.vue';
import HeroPanel from '@/components/HeroPanel.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import SegmentedControl from '@/components/ui/SegmentedControl.vue';
import TextLink from '@/components/ui/TextLink.vue';
import type { CircleSummary, Option, Paginated } from '@/types';

// From CircleController::index (also the home page until there's a dashboard), plus locale/locales shared by
// HandleInertiaRequests.
// TODO: add the Your circles / Popular tabs, search and paging.
const props = defineProps<{
    circles: Paginated<CircleSummary>;
    filters: { search: string };
    locale: string;
    locales: Option[];
}>();

// The selected language is UI state, starting from the server's locale.
const language = ref(props.locale);
</script>

<template>
    <AppLayout>
        <Head title="Circles" />

        <HeroPanel
            title="A place to learn, connect, and build a more sustainable way of living."
            description="Join a Circle, ask the people already farming, building and living in community, and share what you learn."
        >
            <!-- TODO: link to the explore page once it's done. -->
            <Button v-if="$page.props.auth.user" variant="accent" size="lg">Explore Circles</Button>
            <template v-else>
                <Button variant="surface" size="lg" :href="route('login')">Log in</Button>
                <Button variant="accent" size="lg" :href="route('auth.register.create')">Join the Circle</Button>
            </template>
        </HeroPanel>

        <CircleCard v-for="circle in circles.data" :key="circle.public_id" :circle="circle" />
        <Card v-if="circles.data.length === 0" title="No circles yet" description="Circles will show up here once they're started." />

        <footer class="flex flex-col items-center gap-4 py-6">
            <!-- TODO: link to the community rules once they're written. -->
            <TextLink href="#" external>Community rules</TextLink>
            <SegmentedControl v-model="language" shape="pill" label="Language" :options="locales" />
        </footer>
    </AppLayout>
</template>
