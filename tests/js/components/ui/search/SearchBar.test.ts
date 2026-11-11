import { mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import SearchBar from '@/components/ui/search/SearchBar.vue';
import { fakeRouter, page } from '@tests/support';

interface SearchBarProps {
    param: string;
    pageName?: string;
    search: string;
    only: string[];
    label: string;
    placeholder?: string;
}

/** How a search visit reloads the list: only its props, keeping the page where it is and its state. */
const visit = { only: ['things', 'filters'], preserveState: true, preserveScroll: true };

/**
 * A search for one of several paged lists on a page, at an address with another list's page, this list's page and an
 * earlier search in it.
 */
function searchBar(props: Partial<SearchBarProps> = {}, url = '/things/abc?others_page=2&things_page=3&things_search=old'): VueWrapper {
    page.url = url;

    return mount(SearchBar, {
        props: { param: 'things_search', pageName: 'things_page', search: '', only: ['things', 'filters'], label: 'Search things', placeholder: 'Search things by name', ...props },
    });
}

describe('SearchBar', () => {
    afterEach(() => {
        vi.useRealTimers();
    });

    it('is a search form, its box named for what it sets in the address, starting from the current search', () => {
        const wrapper = searchBar({ search: 'old' });
        const box = wrapper.get<HTMLInputElement>('form[role="search"] input');

        expect(box.attributes('name')).toBe('things_search');
        expect(box.attributes('aria-label')).toBe('Search things');
        expect(box.attributes('placeholder')).toBe('Search things by name');
        expect(box.element.value).toBe('old');
    });

    it("searches once typing stops, from the list's first page, keeping the rest of the address", async () => {
        vi.useFakeTimers();
        const wrapper = searchBar();
        const box = wrapper.get('input');

        await box.setValue('g');
        await vi.advanceTimersByTimeAsync(100);
        await box.setValue('grace');
        await vi.advanceTimersByTimeAsync(449);

        expect(fakeRouter.get).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(1);

        expect(fakeRouter.get).toHaveBeenCalledExactlyOnceWith('/things/abc?others_page=2&things_search=grace', {}, visit);
    });

    it('searches straight away on Enter, without searching again once the pause is up', async () => {
        vi.useFakeTimers();
        const wrapper = searchBar();

        await wrapper.get('input').setValue('grace');
        await wrapper.get('form').trigger('submit');
        await vi.advanceTimersByTimeAsync(450);

        expect(fakeRouter.get).toHaveBeenCalledExactlyOnceWith('/things/abc?others_page=2&things_search=grace', {}, visit);
    });

    it('clears the search with the ✕, showing everything again straight away and dropping a search still waiting', async () => {
        vi.useFakeTimers();
        const wrapper = searchBar({ search: 'old' });

        await wrapper.get('input').setValue('olde');
        await wrapper.get('button[aria-label="Clear the search"]').trigger('click');

        expect(fakeRouter.get).toHaveBeenCalledExactlyOnceWith('/things/abc?others_page=2', {}, visit);

        await vi.advanceTimersByTimeAsync(450);

        expect(fakeRouter.get).toHaveBeenCalledOnce();
        expect(wrapper.get<HTMLInputElement>('input').element.value).toBe('');
    });

    it("trims the search, and leaves a blank one out of the address", async () => {
        const wrapper = searchBar();

        await wrapper.get('input').setValue('  grace ');
        await wrapper.get('form').trigger('submit');
        await wrapper.get('input').setValue('   ');
        await wrapper.get('form').trigger('submit');

        expect(fakeRouter.get.mock.calls.map((call) => call[0])).toEqual(['/things/abc?others_page=2&things_search=grace', '/things/abc?others_page=2']);
    });

    it('starts a page list from its first page by default', async () => {
        const wrapper = searchBar({ param: 'search', pageName: undefined }, '/things?page=3');

        await wrapper.get('input').setValue('grace');
        await wrapper.get('form').trigger('submit');

        expect(fakeRouter.get).toHaveBeenCalledWith('/things?search=grace', {}, visit);
    });

    it('has no Search button, since it searches as you type', () => {
        expect(searchBar({ search: 'old' }).findAll('button').map((button) => button.text())).not.toContain('Search');
    });
});
