<script setup lang="ts">
import { nextTick, ref, useId } from 'vue';
import { useEventListener } from '@/composables/useEventListener';
import { useMenuKeyboard } from '@/composables/ui/useMenuKeyboard';

withDefaults(
    defineProps<{
        /** Accessible name for the button and the menu, e.g. "Account menu". */
        label: string;
        /** Which side of the button the menu opens on. */
        side?: 'top' | 'bottom';
        /** "brand" is for use on the dark green header bar. */
        tone?: 'default' | 'brand';
    }>(),
    { side: 'bottom', tone: 'default' },
);

const open = ref(false);
const root = ref<HTMLElement | null>(null);
const trigger = ref<HTMLButtonElement | null>(null);
const panel = ref<HTMLElement | null>(null);
const panelId = useId();

// Escape closes the menu and puts focus back on its button.
const { focusItem, onKeydown } = useMenuKeyboard(panel, open, () => hide(true));

async function show(): Promise<void> {
    open.value = true;
    await nextTick();
    focusItem(0);
}

function hide(returnFocus = false): void {
    open.value = false;

    if (returnFocus) {
        trigger.value?.focus();
    }
}

function toggle(): void {
    if (open.value) {
        hide();
    } else {
        void show();
    }
}

// Choosing an item closes the menu.
function onPanelClick(event: MouseEvent): void {
    if ((event.target as HTMLElement).closest('[role="menuitem"]')) {
        hide();
    }
}

// Tabbing out closes the menu. Clicks elsewhere are handled by the pointerdown listener below, because Safari doesn't focus clicked buttons.
function onFocusout(event: FocusEvent): void {
    if (event.relatedTarget && !root.value?.contains(event.relatedTarget as Node)) {
        hide();
    }
}

useEventListener(document, 'pointerdown', (event) => {
    if (open.value && !root.value?.contains(event.target as Node)) {
        hide();
    }
});
</script>

<template>
    <div ref="root" class="relative" @keydown="onKeydown" @focusout="onFocusout">
        <button
            ref="trigger"
            type="button"
            :class="[
                'grid size-10 shrink-0 place-items-center transition-colors focus-visible:ring-2 focus-visible:outline-none',
                tone === 'brand'
                    ? 'rounded-full bg-brand-foreground/10 text-brand-foreground/80 hover:bg-brand-foreground/20 hover:text-brand-foreground focus-visible:ring-brand-foreground aria-expanded:bg-brand-foreground aria-expanded:text-brand'
                    : 'rounded-control text-muted-foreground hover:bg-secondary hover:text-heading focus-visible:ring-ring aria-expanded:bg-secondary aria-expanded:text-heading',
            ]"
            :aria-label="label"
            aria-haspopup="menu"
            :aria-expanded="open"
            :aria-controls="open ? panelId : undefined"
            @click="toggle"
        >
            <slot name="trigger" />
        </button>

        <div
            v-if="open"
            :id="panelId"
            ref="panel"
            role="menu"
            :aria-label="label"
            :class="[
                'absolute right-0 z-40 flex min-w-44 flex-col rounded-control border border-border bg-surface p-1 shadow-lg',
                side === 'top' ? 'bottom-full mb-2' : 'top-full mt-2',
            ]"
            @click="onPanelClick"
        >
            <slot />
            <!-- NOTE: the arrow sits behind the items (-z-10) so a hovered item covers it, but over the panel's border. -->
            <span
                :class="[
                    'absolute right-3.5 -z-10 size-3 rotate-45 border-border bg-surface',
                    side === 'top' ? '-bottom-1.5 border-r border-b' : '-top-1.5 border-t border-l',
                ]"
                aria-hidden="true"
            />
        </div>
    </div>
</template>
