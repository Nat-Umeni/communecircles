<script setup lang="ts">
import { CircleCheck, TriangleAlert } from '@lucide/vue';

type Variant = 'success' | 'warning';

withDefaults(
    defineProps<{
        variant?: Variant;
    }>(),
    { variant: 'success' },
);

const styles: Record<Variant, { box: string; icon: string }> = {
    success: { box: 'border-success/30 bg-success/10', icon: 'text-success' },
    warning: { box: 'border-warning/30 bg-warning/10', icon: 'text-warning' },
};

const icons = { success: CircleCheck, warning: TriangleAlert };
</script>

<template>
    <div role="status" :class="['flex items-start gap-3 rounded-control border px-4 py-3 text-heading', styles[variant].box]">
        <component :is="icons[variant]" :class="['mt-0.5 size-5 shrink-0', styles[variant].icon]" aria-hidden="true" />
        <div class="min-w-0 flex-1">
            <slot />
        </div>
    </div>
</template>
