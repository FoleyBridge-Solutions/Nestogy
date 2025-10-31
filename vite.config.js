import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import fs from 'fs';

// Check if SSL certificate files exist
const sslKeyPath = '/opt/nestogy/server.key';
const sslCertPath = '/opt/nestogy/server.crt';
const hasSslCerts = fs.existsSync(sslKeyPath) && fs.existsSync(sslCertPath);

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
    server: hasSslCerts ? {
        https: {
            key: fs.readFileSync(sslKeyPath),
            cert: fs.readFileSync(sslCertPath),
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
    } : {
        host: '0.0.0.0',
        port: 5173,
    },
});
