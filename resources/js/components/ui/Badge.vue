<script setup>
defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'accent', 'success', 'warning', 'danger'].includes(value),
    },
});

const variants = {
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
