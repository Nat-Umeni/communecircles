import path from 'node:path';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import inertia from '@inertiajs/vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import themeTokens from './resources/theme/vite-plugin.js';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Nunito', {
                    weights: [600, 700, 800],
                }),
                bunny('Nunito Sans', {
                    weights: [400, 600, 700],
                }),
                bunny('Playfair Display', {
                    weights: [500],
                }),
            ],
        }),
        themeTokens(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        inertia(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            // Ziggy's route() helper ships with the Composer package.
            'ziggy-js': path.resolve('vendor/tightenco/ziggy'),
        },
    },
    server: {
        host: '0.0.0.0', // accept connections from outside the node container
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost', // the address your browser uses to reach Vite
        },
        watch: {
            // NOTE: coverage/ is Vitest's HTML report; watching it reloaded the browser on every coverage run.
            ignored: ['**/storage/framework/views/**', '**/coverage/**'],
        },
    },
});
