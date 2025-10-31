import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import fs from 'fs';

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
    server: {
        https: {
            key: fs.readFileSync('/opt/nestogy/server.key'),
            cert: fs.readFileSync('/opt/nestogy/server.crt'),
        },
        host: '0.0.0.0',
        port: 5173,
        cors: {
            origin: ['https://10.0.3.179:8443', 'https://localhost:8443'],
            credentials: true,
        },
        hmr: {
            host: '10.0.3.179',
            protocol: 'wss',
        },
    },
});
