<script setup lang="ts">
const model = defineModel<string>({ default: '' });
</script>

<template>
    <textarea
        v-model="model"
        class="min-h-28 w-full rounded-control border border-border bg-surface px-4 py-3 text-foreground transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 focus-visible:outline-none disabled:opacity-50 aria-invalid:border-danger aria-invalid:focus-visible:ring-danger/30"
    />
</template>
