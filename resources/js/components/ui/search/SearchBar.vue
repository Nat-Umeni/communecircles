<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useDebouncedFn } from '@/composables/useDebouncedFn';
import SearchInput from '@/components/ui/search/SearchInput.vue';

const props = withDefaults(
    defineProps<{
        /** What the search sets in the address, also the box's name, e.g. "search" or "members_search". */
        param: string;
        /** The list's page in the address, dropped so a new search starts from its first page, e.g. "members_page". */
        pageName?: string;
        /** The search the list shows now, from the server; the box starts with it. */
        search: string;
        /** The props a search reloads: the list and the search it's for, e.g. ['members', 'filters']. */
        only: string[];
        /** The box's accessible name, e.g. "Search members". */
        label: string;
        placeholder?: string;
    }>(),
    { pageName: 'page', placeholder: undefined },
);

/** How long typing has to stop before the search is sent. */
const SEARCH_PAUSE_MS = 450;

const page = usePage();
const text = ref(props.search);

// A search goes to the page's own address with the search in it and the list back on its first page, keeping the rest
// (e.g. another list's page), and reloads only the list, keeping the page where it is. Typing searches once it stops
// for a moment; Enter and the ✕ search straight away, dropping one still waiting (and a newer search cancels one still
// loading).
function find(): void {
    const search = text.value.trim();
    const url = new URL(page.url, window.location.origin);

    url.searchParams.delete(props.pageName);
    if (search === '') {
        url.searchParams.delete(props.param);
    } else {
        url.searchParams.set(props.param, search);
    }

    findSoon.cancel();
    router.get(url.pathname + url.search, {}, { only: props.only, preserveState: true, preserveScroll: true });
}

const findSoon = useDebouncedFn(find, SEARCH_PAUSE_MS);
</script>

<template>
    <form role="search" @submit.prevent="find">
        <SearchInput v-model="text" :name="param" :aria-label="label" :placeholder="placeholder" @update:model-value="findSoon.call()" @clear="find" />
    </form>
</template>
