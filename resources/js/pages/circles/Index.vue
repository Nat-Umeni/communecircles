<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import CircleCard from '@/components/circles/CircleCard.vue';
import HeroPanel from '@/components/HeroPanel.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import Pagination from '@/components/ui/Pagination.vue';
import SegmentedControl from '@/components/ui/SegmentedControl.vue';
import TextLink from '@/components/ui/TextLink.vue';
import Input from '@/components/ui/form/Input.vue';
import type { CircleOfMine, CircleSummary, Option, Paginated } from '@/types';

// From CircleController::index (also the home page until there's a dashboard), plus locale/locales shared by
// HandleInertiaRequests.
const props = defineProps<{
    /** Popular: approved circles, most members first, searched by name. */
    circles: Paginated<CircleSummary>;
    filters: { search: string };
    /** Every circle the member is in, approved or not; null for guests. */
    your_circles: CircleOfMine[] | null;
    locale: string;
    locales: Option[];
}>();

const lists: Option[] = [
    { value: 'yours', label: 'Your circles' },
    { value: 'popular', label: 'Popular' },
];

// The selected list, search text and language are UI state, starting from the server's props. Members start on their
// own circles unless they have none, or are searching or paging Popular (its links load this page afresh).
const list = ref(props.your_circles?.length && !props.filters.search && props.circles.current_page === 1 ? 'yours' : 'popular');
const search = ref(props.filters.search);
const language = ref(props.locale);

function find(): void {
    const text = search.value.trim();

    router.get(route('circles.index'), text === '' ? {} : { search: text }, { preserveState: true, preserveScroll: true });
}
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

        <SegmentedControl v-if="your_circles" v-model="list" block label="Circles to show" :options="lists" />

        <template v-if="list === 'yours'">
            <CircleCard v-for="circle in your_circles" :key="circle.public_id" :circle="circle" />
            <Card v-if="!your_circles?.length" title="You're not in any circles yet" description="Find one under Popular, or start your own from the + menu." />
        </template>
        <template v-else>
            <form role="search" class="flex gap-2" @submit.prevent="find">
                <Input v-model="search" type="search" name="search" aria-label="Search circles" placeholder="Search circles by name" />
                <Button type="submit" variant="surface" size="lg" class="shrink-0">Search</Button>
            </form>
            <CircleCard v-for="circle in circles.data" :key="circle.public_id" :circle="circle" />
            <Card v-if="circles.data.length === 0 && filters.search" :title="`No circles match “${filters.search}”`" description="Try another name." />
            <Card v-else-if="circles.data.length === 0" title="No circles yet" description="Circles will show up here once they're started." />
            <Pagination :paginator="circles" />
        </template>

        <footer class="flex flex-col items-center gap-4 py-6">
            <!-- TODO: link to the community rules once they're written. -->
            <TextLink href="#" external>Community rules</TextLink>
            <SegmentedControl v-model="language" shape="pill" label="Language" :options="locales" />
        </footer>
    </AppLayout>
</template>
