import path from 'node:path';
import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vitest/config';

// Component tests for resources/js. Kept apart from vite.config.js so tests don't load the Laravel,
// Tailwind, Inertia and theme-token plugins or the dev-server settings.
export default defineConfig({
    plugins: [
        vue({
            template: {
                // Same as vite.config.js: leave "/images/..." paths as plain URLs.
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve('resources/js'),
            'ziggy-js': path.resolve('vendor/tightenco/ziggy'),
        },
    },
    test: {
        environment: 'happy-dom',
        include: ['tests/js/**/*.test.ts'],
        setupFiles: ['tests/js/setup.ts'],
        server: {
            deps: {
                // NOTE: processed by Vite rather than loaded as-is, so the router mock in tests/js/setup.ts
                // also reaches the imports inside @inertiajs/vue3 (useForm, Link).
                inline: [/@inertiajs\//],
            },
        },
        coverage: {
            provider: 'v8',
            include: ['resources/js/**/*.{ts,vue}'],
            // NOTE: app.ts only boots Inertia, and types/ holds type declarations with no runtime code.
            exclude: ['resources/js/app.ts', 'resources/js/types/**'],
            thresholds: {
                lines: 100,
                functions: 100,
                branches: 100,
                statements: 100,
            },
        },
    },
});
