import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import PlaceSearch from '@/components/circles/PlaceSearch.vue';
import type { Place } from '@/types';
import { dropConnection, respond, waitingRequests } from '@tests/support';

const totnes: Place = { geonames_id: 2635610, name: 'Totnes', region: 'England', country_code: 'GB', country_name: 'United Kingdom', latitude: 50.43107, longitude: -3.6843 };
const vaduz: Place = { geonames_id: 3042030, name: 'Vaduz', region: null, country_code: 'LI', country_name: 'Liechtenstein', latitude: 47.14151, longitude: 9.52154 };

/**
 * PlaceSearch bound with v-model, as a form uses it.
 */
function mountSearch(place: Place | null = null): VueWrapper {
    const wrapper: VueWrapper = mount(PlaceSearch, {
        props: { modelValue: place, 'onUpdate:modelValue': (value: Place | null) => wrapper.setProps({ modelValue: value }) },
        attachTo: document.body,
    });

    return wrapper;
}

/**
 * Types into the search and waits for the pause that sends it.
 */
async function search(wrapper: VueWrapper, text: string): Promise<void> {
    await wrapper.get('input').setValue(text);
    await vi.advanceTimersByTimeAsync(300);
}

function options(wrapper: VueWrapper): string[] {
    return wrapper.findAll('[role="option"]').map((option) => option.text());
}

function status(wrapper: VueWrapper): string {
    return wrapper.get('[role="status"]').text();
}

describe('PlaceSearch', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
        document.body.innerHTML = '';
    });

    it('searches the towns and villages once typing stops', async () => {
        const wrapper = mountSearch();

        await search(wrapper, 'Tot');

        expect(waitingRequests()).toEqual(['/places.search?search=Tot']);
    });

    it('says it is searching', async () => {
        const wrapper = mountSearch();

        await search(wrapper, 'Tot');

        expect(status(wrapper)).toBe('Searching…');
    });

    it('lists the places found', async () => {
        const wrapper = mountSearch();

        await search(wrapper, 'Tot');
        await respond(200, { places: [totnes, vaduz] });

        const input = wrapper.get('input');
        expect(input.attributes('role')).toBe('combobox');
        expect(input.attributes('aria-expanded')).toBe('true');
        expect(input.attributes('aria-controls')).toBe(wrapper.get('[role="listbox"]').attributes('id'));
        expect(options(wrapper)).toEqual(['Totnes, England, United Kingdom', 'Vaduz, Liechtenstein']);
        expect(status(wrapper)).toBe('');
    });

    it('chooses the place that is clicked', async () => {
        const wrapper = mountSearch();

        await search(wrapper, 'Tot');
        await respond(200, { places: [totnes, vaduz] });
        await wrapper.findAll('[role="option"]')[1].trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([[vaduz]]);
        expect(wrapper.get('input').element.value).toBe('Vaduz, Liechtenstein');
        expect(wrapper.find('[role="listbox"]').exists()).toBe(false);
        expect(wrapper.get('input').attributes('aria-expanded')).toBe('false');
    });

    it('chooses a place with the arrow keys and Enter', async () => {
        const wrapper = mountSearch();
        const input = wrapper.get('input');

        await search(wrapper, 'Tot');
        await respond(200, { places: [totnes, vaduz] });
        await input.trigger('keydown', { key: 'ArrowDown' });
        await input.trigger('keydown', { key: 'ArrowDown' });
        await input.trigger('keydown', { key: 'ArrowUp' });

        const highlighted = wrapper.get('[role="option"][aria-selected="true"]');
        expect(highlighted.text()).toBe('Totnes, England, United Kingdom');
        expect(input.attributes('aria-activedescendant')).toBe(highlighted.attributes('id'));

        const enter = new KeyboardEvent('keydown', { key: 'Enter', cancelable: true });
        input.element.dispatchEvent(enter);
        await wrapper.vm.$nextTick();

        expect(enter.defaultPrevented).toBe(true);
        expect(wrapper.emitted('update:modelValue')).toEqual([[totnes]]);
    });

    it('shows the chosen place, which can be cleared', async () => {
        const wrapper = mountSearch(totnes);
        const input = wrapper.get('input');

        expect(input.element.value).toBe('Totnes, England, United Kingdom');

        await wrapper.get('button[aria-label="Clear the place"]').trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([[null]]);
        expect(input.element.value).toBe('');
        expect(document.activeElement).toBe(input.element);
        expect(wrapper.find('button[aria-label="Clear the place"]').exists()).toBe(false);
    });

    it('forgets the chosen place when the search is typed over', async () => {
        const wrapper = mountSearch(totnes);

        await wrapper.get('input').setValue('Dart');

        expect(wrapper.emitted('update:modelValue')).toEqual([[null]]);
    });

    it('says when no place matches', async () => {
        const wrapper = mountSearch();

        await search(wrapper, 'Zzyzx');
        await respond(200, { places: [] });

        expect(status(wrapper)).toBe('No towns or villages match “Zzyzx”.');
        expect(wrapper.find('[role="listbox"]').exists()).toBe(false);
    });

    it("shows the server's message when search isn't available", async () => {
        const wrapper = mountSearch();

        await search(wrapper, 'Tot');
        await respond(503, { message: "Place search isn't available right now. Try again in a little while." });

        expect(status(wrapper)).toBe("Place search isn't available right now. Try again in a little while.");
    });

    it("says search isn't available when the connection fails", async () => {
        const wrapper = mountSearch();

        await search(wrapper, 'Tot');
        await dropConnection();

        expect(status(wrapper)).toBe("Place search isn't available right now. Try again in a little while, or leave it empty.");
    });

    it("keeps focus in the search while a place is clicked, so the list doesn't close under the click", async () => {
        const wrapper = mountSearch();

        await search(wrapper, 'Tot');
        await respond(200, { places: [totnes, vaduz] });

        const mousedown = new MouseEvent('mousedown', { cancelable: true });
        wrapper.get('[role="option"]').element.dispatchEvent(mousedown);

        expect(mousedown.defaultPrevented).toBe(true);
    });

    it('ignores the arrow keys before anything is listed', async () => {
        const wrapper = mountSearch();
        const input = wrapper.get('input');

        await input.trigger('keydown', { key: 'ArrowDown' });

        expect(input.attributes('aria-activedescendant')).toBeUndefined();
    });

    it("doesn't search for fewer than two letters", async () => {
        const wrapper = mountSearch();

        await search(wrapper, ' T ');

        expect(waitingRequests()).toEqual([]);
        expect(status(wrapper)).toBe('');
    });

    it("doesn't search until typing stops", async () => {
        const wrapper = mountSearch();

        await wrapper.get('input').setValue('To');
        expect(waitingRequests()).toEqual([]);

        await search(wrapper, 'Tot');
        expect(waitingRequests()).toEqual(['/places.search?search=Tot']);
    });

    it('drops a search that is typed over', async () => {
        const wrapper = mountSearch();

        await search(wrapper, 'Tot');
        await wrapper.get('input').setValue('Totn');
        await flushPromises();

        expect(waitingRequests()).toEqual([]);
        expect(status(wrapper)).toBe('');
    });

    it('closes the list on Escape', async () => {
        const wrapper = mountSearch();

        await search(wrapper, 'Tot');
        await respond(200, { places: [totnes, vaduz] });
        await wrapper.get('input').trigger('keydown', { key: 'Escape' });

        expect(wrapper.find('[role="listbox"]').exists()).toBe(false);
        expect(wrapper.get('input').attributes('aria-expanded')).toBe('false');
    });

    it('closes the list when focus leaves the search', async () => {
        const wrapper = mountSearch();

        await search(wrapper, 'Tot');
        await respond(200, { places: [totnes, vaduz] });
        await wrapper.get('input').trigger('focusout');

        expect(wrapper.find('[role="listbox"]').exists()).toBe(false);
    });

    it('leaves Enter to the form unless a listed place is highlighted', async () => {
        const wrapper = mountSearch();
        const input = wrapper.get('input');

        await search(wrapper, 'Tot');
        await respond(200, { places: [totnes, vaduz] });

        const nothingHighlighted = new KeyboardEvent('keydown', { key: 'Enter', cancelable: true });
        input.element.dispatchEvent(nothingHighlighted);

        await input.trigger('keydown', { key: 'ArrowDown' });
        await input.trigger('keydown', { key: 'Escape' });

        const listClosed = new KeyboardEvent('keydown', { key: 'Enter', cancelable: true });
        input.element.dispatchEvent(listClosed);
        await wrapper.vm.$nextTick();

        expect(nothingHighlighted.defaultPrevented).toBe(false);
        expect(listClosed.defaultPrevented).toBe(false);
        expect(wrapper.emitted('update:modelValue')).toBeUndefined();
    });
});
