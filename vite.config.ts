import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import ui from '@nuxt/ui/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/cms.css',
                'resources/js/cms.ts',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        ui({
            inertia: true,
            ui: {
                colors: {
                    primary: 'zinc',
                    neutral: 'zinc',
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '@cms': '/resources/js/cms',
        },
    },
});
