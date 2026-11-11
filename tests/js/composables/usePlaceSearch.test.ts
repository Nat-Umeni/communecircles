import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { usePlaceSearch } from '@/composables/usePlaceSearch';
import type { Place } from '@/types';
import { dropConnection, respond, waitingRequests, withSetup } from '@tests/support';

const totnes: Place = { geonames_id: 2635610, name: 'Totnes', region: 'England', country_code: 'GB', country_name: 'United Kingdom', latitude: 50.43107, longitude: -3.6843 };

function placeSearch(initial = '') {
    return withSetup(() => usePlaceSearch(initial)).result;
}

describe('usePlaceSearch', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('starts with the text it is given and no places', () => {
        const { text, places, searching, failure } = placeSearch('Totnes, England, United Kingdom');

        expect(text.value).toBe('Totnes, England, United Kingdom');
        expect(places.value).toEqual([]);
        expect(searching.value).toBe(false);
        expect(failure.value).toBeNull();
    });

    it('lets the text be set without searching, e.g. to a chosen place', () => {
        const { text } = placeSearch();

        text.value = 'Totnes, England, United Kingdom';

        expect(text.value).toBe('Totnes, England, United Kingdom');
        expect(waitingRequests()).toEqual([]);
    });

    it('searches once typing stops, and finds the places', async () => {
        const { text, places, searching, search } = placeSearch();

        const found = search('Tot');

        expect(text.value).toBe('Tot');
        await vi.advanceTimersByTimeAsync(299);
        expect(waitingRequests()).toEqual([]);

        await vi.advanceTimersByTimeAsync(1);
        expect(waitingRequests()).toEqual(['/places.search?search=Tot']);
        expect(searching.value).toBe(true);

        await respond(200, { places: [totnes] });

        await expect(found).resolves.toBe(true);
        expect(places.value).toEqual([totnes]);
        expect(searching.value).toBe(false);
    });

    it("doesn't search for fewer than 2 characters", async () => {
        const { search } = placeSearch();

        await expect(search(' T ')).resolves.toBe(false);
        await vi.advanceTimersByTimeAsync(300);

        expect(waitingRequests()).toEqual([]);
    });

    it('drops a search still waiting to be sent when typing goes on', async () => {
        const { search } = placeSearch();

        const first = search('Tot');
        await vi.advanceTimersByTimeAsync(100);
        void search('Totn');

        await expect(first).resolves.toBe(false);
        await vi.advanceTimersByTimeAsync(300);
        expect(waitingRequests()).toEqual(['/places.search?search=Totn']);
    });

    it('cancels a search already sent when typing goes on', async () => {
        const { failure, search } = placeSearch();

        const first = search('Tot');
        await vi.advanceTimersByTimeAsync(300);
        void search('Totn');

        await expect(first).resolves.toBe(false);
        expect(failure.value).toBeNull();
        await vi.advanceTimersByTimeAsync(300);
        expect(waitingRequests()).toEqual(['/places.search?search=Totn']);
    });

    it("gives the server's message when the search fails", async () => {
        const { failure, search } = placeSearch();

        const found = search('Tot');
        await vi.advanceTimersByTimeAsync(300);
        await respond(503, { message: "Place search isn't available right now. Try again in a little while." });

        await expect(found).resolves.toBe(false);
        expect(failure.value).toBe("Place search isn't available right now. Try again in a little while.");
    });

    it("says the search isn't available when the server can't be reached, and forgets that once searching again", async () => {
        const { failure, search } = placeSearch();

        const found = search('Tot');
        await vi.advanceTimersByTimeAsync(300);
        await dropConnection();

        await expect(found).resolves.toBe(false);
        expect(failure.value).toBe("Place search isn't available right now. Try again in a little while, or leave it empty.");

        void search('Totn');

        expect(failure.value).toBeNull();
    });
});
