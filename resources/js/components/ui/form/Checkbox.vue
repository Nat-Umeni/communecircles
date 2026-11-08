<script setup lang="ts">
const model = defineModel<boolean>({ default: false });
</script>

<template>
    <input
        v-model="model"
        type="checkbox"
        class="size-5 shrink-0 cursor-pointer rounded-sm accent-primary focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-surface focus-visible:outline-none disabled:cursor-default disabled:opacity-50"
    />
</template>
