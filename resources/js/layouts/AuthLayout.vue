<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import Card from '@/components/ui/Card.vue';

defineProps<{
    /** The page heading, e.g. "Welcome back". */
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-dvh flex-col">
        <header class="flex h-16 shrink-0 items-center gap-3 bg-brand px-4 text-brand-foreground lg:h-20 lg:px-6">
            <AppLogo :href="route('home')" />
            <!-- NOTE: there's no room next to the logo on phones, so the toggle moves under the page there. -->
            <div class="ml-auto hidden sm:block">
                <ThemeToggle tone="brand" />
            </div>
        </header>

        <main class="flex flex-1 justify-center px-4 py-10 sm:px-6 sm:py-16">
            <div class="flex w-full max-w-lg flex-col gap-6">
                <header class="flex flex-col gap-2 text-center">
                    <h1 class="text-3xl font-extrabold sm:text-4xl">{{ title }}</h1>
                    <p v-if="description" class="text-lg text-muted-foreground">{{ description }}</p>
                </header>

                <!-- The page's form. -->
                <Card>
                    <slot />
                </Card>

                <!-- Links under the card, e.g. "New here? Join the Circle". -->
                <p v-if="$slots.footer" class="text-center text-muted-foreground">
                    <slot name="footer" />
                </p>

                <div class="flex justify-center pt-2 sm:hidden">
                    <ThemeToggle />
                </div>
            </div>
        </main>
    </div>
</template>
