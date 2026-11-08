<script setup lang="ts">
withDefaults(
    defineProps<{
        /** File name in public/images/icons, without the extension, e.g. "explore". */
        name: string;
        size?: number;
        disabled?: boolean;
        /** Leave empty when a visible label sits next to the icon. */
        alt?: string;
    }>(),
    { size: 40, alt: '' },
);
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
