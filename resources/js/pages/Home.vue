<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import CircleCard from '@/components/CircleCard.vue';
import HeroPanel from '@/components/HeroPanel.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import SegmentedControl from '@/components/ui/SegmentedControl.vue';
import TextLink from '@/components/ui/TextLink.vue';
import type { Circle, Option } from '@/types';

// From app/Http/Controllers/HomeController.php, plus locale/locales shared by HandleInertiaRequests.
const props = defineProps<{
    circles: Circle[];
    locale: string;
    locales: Option[];
}>();

const feeds: Option[] = [
    { value: 'latest', label: 'Latest' },
    { value: 'circles', label: 'Circles' },
];

// The selected tab and language are UI state; the language starts from the server's locale.
const feed = ref('circles');
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
            <Button v-if="$page.props.auth.user" variant="accent" size="lg">Explore communes</Button>
            <template v-else>
                <!-- TODO: link to the login page once it's done. -->
                <Button variant="surface" size="lg">Log in</Button>
                <Button variant="accent" size="lg" :href="route('auth.register.create')">Join the Circle</Button>
            </template>
        </HeroPanel>

        <!--
            Section icon grid from the mockup, hidden for now because the sidebar already links to every section.
        <Card>
            <ul class="grid grid-cols-2 gap-x-4 gap-y-8 py-2 text-center font-display text-lg tracking-wide text-heading uppercase sm:grid-cols-4 sm:text-xl">
                <li>
                    <Link :href="route('home')" class="flex flex-col items-center gap-3 rounded-card p-2 transition-colors hover:bg-secondary">
                        <BrandIcon name="circles" :size="88" />Circles
                    </Link>
                </li>
                <li class="flex cursor-pointer flex-col items-center gap-3 rounded-card p-2 hover:bg-secondary"><BrandIcon name="explore" :size="88" />Explore</li>
                <li class="flex cursor-pointer flex-col items-center gap-3 rounded-card p-2 hover:bg-secondary"><BrandIcon name="community" :size="88" />Community</li>
                <li class="flex cursor-pointer flex-col items-center gap-3 rounded-card p-2 hover:bg-secondary"><BrandIcon name="skills" :size="88" />Skills</li>
                <li class="flex cursor-pointer flex-col items-center gap-3 rounded-card p-2 hover:bg-secondary"><BrandIcon name="exchange" :size="88" />Exchange</li>
                <li class="flex cursor-pointer flex-col items-center gap-3 rounded-card p-2 hover:bg-secondary"><BrandIcon name="learn" :size="88" />Learn</li>
                <li class="flex flex-col items-center gap-3 p-2 text-subtle-foreground">
                    <span class="relative"><BrandIcon name="seeds-plants" :size="88" disabled /><Badge class="absolute -top-2 left-1/2">Soon</Badge></span>
                    Seeds &amp; Plants
                </li>
                <li class="flex cursor-pointer flex-col items-center gap-3 rounded-card p-2 hover:bg-secondary"><BrandIcon name="resources" :size="88" />Resources</li>
            </ul>
        </Card>
        -->

        <SegmentedControl v-model="feed" block label="Feed" :options="feeds" />

        <template v-if="feed === 'circles'">
            <CircleCard v-for="circle in circles" :key="circle.name" v-bind="circle" />
        </template>
        <Card v-else title="No posts yet" description="New posts from every circle will show up here." />

        <footer class="flex flex-col items-center gap-4 py-6">
            <!-- TODO: link to the community rules once they're written. -->
            <TextLink href="#" external>Community rules</TextLink>
            <SegmentedControl v-model="language" shape="pill" label="Language" :options="locales" />
        </footer>
    </AppLayout>
</template>
