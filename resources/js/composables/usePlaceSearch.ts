import { HttpCancelledError, HttpResponseError } from '@inertiajs/core';
import { useHttp } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { ComputedRef, Ref, WritableComputedRef } from 'vue';
import { route } from 'ziggy-js';
import type { Place } from '@/types';

/** How long typing has to stop before the search is sent, so each keystroke isn't a request. */
const PAUSE_MS = 300;

/** The shortest text worth searching for. */
const MIN_LENGTH = 2;

/**
 * Searching GeoNames' towns and villages (PlaceController) as someone types: the search text, the places found, whether
 * a search is on its way, and why the last one failed.
 */
export function usePlaceSearch(initial: string): {
    text: WritableComputedRef<string>;
    places: ComputedRef<Place[]>;
    searching: ComputedRef<boolean>;
    failure: Ref<string | null>;
    search: (text: string) => Promise<boolean>;
} {
    const http = useHttp<{ search: string }, { places: Place[] }>({ search: initial });
    const failure = ref<string | null>(null);
    let waiting: { timer: ReturnType<typeof setTimeout>; settle: (found: boolean) => void } | null = null;

    /**
     * Search for the text once typing stops, if it's long enough. A search still waiting, or already sent, is for text
     * that's gone, so it's dropped. Resolves true once places are back; false if the text was too short, more typing
     * replaced it, or it failed (failure then says why).
     */
    function search(text: string): Promise<boolean> {
        http.search = text;
        http.cancel();
        failure.value = null;

        if (waiting) {
            clearTimeout(waiting.timer);
            waiting.settle(false);
            waiting = null;
        }

        if (text.trim().length < MIN_LENGTH) {
            return Promise.resolve(false);
        }

        return new Promise((settle) => {
            waiting = {
                settle,
                timer: setTimeout(() => {
                    waiting = null;
                    void send().then(settle);
                }, PAUSE_MS),
            };
        });
    }

    async function send(): Promise<boolean> {
        try {
            await http.get(route('places.search'));

            return true;
        } catch (error) {
            if (!(error instanceof HttpCancelledError)) {
                failure.value =
                    error instanceof HttpResponseError
                        ? JSON.parse(error.response.data).message
                        : "Place search isn't available right now. Try again in a little while, or leave it empty.";
            }

            return false;
        }
    }

    return {
        text: computed({
            get: () => http.search,
            set: (text) => {
                http.search = text;
            },
        }),
        places: computed(() => http.response?.places ?? []),
        searching: computed(() => http.processing),
        failure,
        search,
    };
}
