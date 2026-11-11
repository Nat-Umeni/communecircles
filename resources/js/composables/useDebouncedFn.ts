import { onBeforeUnmount } from 'vue';

/**
 * Run the function once calls to call() stop for the pause, with the last call's arguments, e.g. to search once
 * typing stops. cancel() drops a run still waiting, as does the component using this going away.
 */
export function useDebouncedFn<A extends unknown[]>(fn: (...args: A) => void, pauseMs: number): { call: (...args: A) => void; cancel: () => void } {
    let timer: ReturnType<typeof setTimeout> | undefined;

    function cancel(): void {
        clearTimeout(timer);
    }

    function call(...args: A): void {
        cancel();
        timer = setTimeout(() => fn(...args), pauseMs);
    }

    onBeforeUnmount(cancel);

    return { call, cancel };
}
