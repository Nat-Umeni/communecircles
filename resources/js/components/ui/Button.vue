<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'accent', 'surface', 'ghost'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
    href: {
        type: String,
        default: null,
    },
    type: {
        type: String,
        default: 'button',
    },
});

const variants = {
    primary: 'bg-primary text-primary-foreground hover:bg-primary/90',
    secondary: 'bg-secondary text-secondary-foreground hover:bg-highlight',
    accent: 'bg-accent text-accent-foreground hover:bg-accent/85',
    surface: 'border border-border bg-surface text-heading hover:bg-secondary',
    ghost: 'text-heading hover:bg-secondary',
};

const sizes = {
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
