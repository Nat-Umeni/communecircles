import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Listen for an event on the window or document while the component using this is mounted, and stop when it goes.
 */
export function useEventListener<K extends keyof WindowEventMap>(target: Window, type: K, listener: (event: WindowEventMap[K]) => void): void;
export function useEventListener<K extends keyof DocumentEventMap>(target: Document, type: K, listener: (event: DocumentEventMap[K]) => void): void;
export function useEventListener(target: Window | Document, type: string, listener: (event: Event) => void): void {
    onMounted(() => target.addEventListener(type, listener));
    onBeforeUnmount(() => target.removeEventListener(type, listener));
}
