import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/client-portal.css',
                'resources/js/app.js',
                'resources/js/contract-clauses.js',
                'resources/js/it-documentation-diagram.js',
                'resources/js/components/settings.js',
                'resources/js/legacy/quote-integration.js',
                'resources/js/legacy/quote-integration-simple.js',
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
                    vendor: ['axios'],
                    charts: ['chart.js'],
                    utils: ['date-fns', 'flatpickr', 'tom-select'],
                    terminal: ['@xterm/xterm', '@xterm/addon-fit', '@xterm/addon-search', '@xterm/addon-web-links'],
                },
            },
        },
    },
});
