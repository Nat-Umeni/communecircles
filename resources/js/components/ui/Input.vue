<script setup lang="ts">
const model = defineModel<string>({ default: '' });
</script>

<template>
    <input
        v-model="model"
        class="h-12 w-full rounded-control border border-border bg-surface px-4 text-foreground transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 focus-visible:outline-none disabled:opacity-50"
    />
</template>
