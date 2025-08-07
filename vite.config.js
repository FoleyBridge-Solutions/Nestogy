import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '~': '/resources',
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['alpinejs', 'axios'],
                    charts: ['chart.js'],
                    utils: ['date-fns', 'flatpickr', 'tom-select'],
                },
            },
        },
    },
    server: {
        hmr: {
            host: 'localhost',
        },
    },
});
