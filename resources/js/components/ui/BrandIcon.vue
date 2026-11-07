<script setup>
defineProps({
    // File name in public/images/icons, without the extension, e.g. "explore".
    name: {
        type: String,
        required: true,
    },
    size: {
        type: Number,
        default: 40,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    // Leave empty when a visible label sits next to the icon.
    alt: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <img
        :src="`/images/icons/${name}.png`"
        :width="size"
        :height="size"
        :alt="alt"
        draggable="false"
        :class="['shrink-0 select-none', disabled && 'opacity-50 grayscale-[30%]']"
    />
</template>
