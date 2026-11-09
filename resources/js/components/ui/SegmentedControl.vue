<script setup lang="ts">
import type { Option } from '@/types';

const model = defineModel<string>({ required: true });

withDefaults(
    defineProps<{
        options: Option[];
        shape?: 'rounded' | 'pill';
        /** "brand" is for use on the dark green header bar. */
        tone?: 'default' | 'brand';
        /** Stretch to full width with equal-width segments, e.g. for tabs. */
        block?: boolean;
        label?: string;
    }>(),
    { shape: 'rounded', tone: 'default' },
);
</script>

<template>
    <div
        role="group"
        :aria-label="label"
        :class="[
            'items-center gap-1 p-1',
            block ? 'flex w-full' : 'inline-flex',
            shape === 'pill' ? 'rounded-full' : 'rounded-control',
            tone === 'brand' ? 'bg-brand-foreground/10' : 'border border-border bg-surface',
        ]"
    >
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            :aria-pressed="model === option.value"
            :title="option.icon ? option.label : undefined"
            :class="[
                'inline-flex items-center justify-center gap-2 font-heading font-bold transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                block && 'flex-1',
                shape === 'pill' ? 'rounded-full' : 'rounded-[calc(var(--radius-control)-4px)]',
                option.icon ? 'size-8' : 'h-10 px-5',
                model === option.value
                    ? tone === 'brand'
                        ? 'bg-brand-foreground text-brand'
                        : 'bg-primary text-primary-foreground'
                    : tone === 'brand'
                      ? 'text-brand-foreground/80 hover:bg-brand-foreground/10 hover:text-brand-foreground'
                      : 'text-muted-foreground hover:bg-secondary hover:text-heading',
            ]"
            @click="model = option.value"
        >
            <component :is="option.icon" v-if="option.icon" class="size-4" aria-hidden="true" />
            <span :class="option.icon && 'sr-only'">{{ option.label }}</span>
        </button>
    </div>
</template>
