<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import CircleCard from '@/components/CircleCard.vue';
import HeroPanel from '@/components/HeroPanel.vue';
import Card from '@/components/ui/Card.vue';
import SegmentedControl from '@/components/ui/SegmentedControl.vue';
import TextLink from '@/components/ui/TextLink.vue';

// From app/Http/Controllers/HomeController.php, plus locale/locales shared by HandleInertiaRequests.
const props = defineProps({
    title: { type: String, required: true },
    hero: { type: Object, required: true },
    feeds: { type: Array, required: true },
    activeFeed: { type: String, required: true },
    latestEmpty: { type: Object, required: true },
    circles: { type: Array, required: true },
    communityRules: { type: Object, required: true },
    locale: { type: String, required: true },
    locales: { type: Array, required: true },
});

// The selected tab and language are UI state, starting from what the server sent.
const feed = ref(props.activeFeed);
const language = ref(props.locale);
</script>

<template>
    <AppLayout>
        <Head :title="title" />

        <HeroPanel v-bind="hero" />

        <!--
            Section icon grid from the mockup, hidden for now because the sidebar already links to every section.
            To restore it: remove this comment's opening and closing lines, then add
            `Link` to the @inertiajs/vue3 import and import BrandIcon and Badge from @/components/ui/.
        <Card>
            <ul class="grid grid-cols-2 gap-x-4 gap-y-8 py-2 text-center font-display text-lg tracking-wide text-heading uppercase sm:grid-cols-4 sm:text-xl">
                <li>
                    <Link :href="$page.props.homeUrl" class="flex flex-col items-center gap-3 rounded-card p-2 transition-colors hover:bg-secondary">
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
        <Card v-else :title="latestEmpty.title" :description="latestEmpty.description" />

        <footer class="flex flex-col items-center gap-4 py-6">
            <TextLink :href="communityRules.href" external>{{ communityRules.label }}</TextLink>
            <SegmentedControl v-model="language" shape="pill" label="Language" :options="locales" />
        </footer>
    </AppLayout>
</template>
