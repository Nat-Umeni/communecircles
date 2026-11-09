<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type Variant = 'primary' | 'secondary' | 'accent' | 'surface' | 'ghost' | 'danger';
type Size = 'sm' | 'md' | 'lg';

const props = withDefaults(
    defineProps<{
        variant?: Variant;
        size?: Size;
        /** With no href the button renders as a plain <button>. */
        href?: string;
        type?: 'button' | 'submit' | 'reset';
    }>(),
    { variant: 'primary', size: 'md', type: 'button' },
);

const variants: Record<Variant, string> = {
    primary: 'bg-primary text-primary-foreground hover:bg-primary/90',
    secondary: 'bg-secondary text-secondary-foreground hover:bg-highlight',
    accent: 'bg-accent text-accent-foreground hover:bg-accent/85',
    surface: 'border border-border bg-surface text-heading hover:bg-secondary',
    ghost: 'text-heading hover:bg-secondary',
    // For actions that can't be undone, like deleting.
    danger: 'bg-danger text-danger-foreground hover:bg-danger/90',
};

const sizes: Record<Size, string> = {
    sm: 'h-9 px-3.5 text-sm',
    md: 'h-11 px-5 text-base',
    lg: 'h-14 px-6 text-lg',
};

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-control font-heading font-bold whitespace-nowrap transition-colors',
    'focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none',
    'disabled:pointer-events-none disabled:opacity-50',
    variants[props.variant],
    sizes[props.size],
]);
</script>

<template>
    <Link v-if="href" :href="href" :class="classes">
        <slot />
    </Link>
    <button v-else :type="type" :class="classes">
        <slot />
    </button>
</template>
