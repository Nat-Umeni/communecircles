<script setup lang="ts">
type Variant = 'primary' | 'secondary' | 'accent' | 'success' | 'warning' | 'danger';

withDefaults(
    defineProps<{
        variant?: Variant;
    }>(),
    { variant: 'primary' },
);

const variants: Record<Variant, string> = {
    primary: 'bg-primary text-primary-foreground',
    secondary: 'bg-secondary text-secondary-foreground',
    accent: 'bg-accent text-accent-foreground',
    success: 'bg-success text-success-foreground',
    warning: 'bg-warning text-warning-foreground',
    danger: 'bg-danger text-danger-foreground',
};
</script>

<template>
    <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 font-heading text-xs font-extrabold tracking-wide uppercase', variants[variant]]">
        <slot />
    </span>
</template>
