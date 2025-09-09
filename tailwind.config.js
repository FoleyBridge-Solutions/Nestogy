import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/livewire/flux/stubs/**/*.blade.php',
        './vendor/livewire/flux/dist/flux.css',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/Livewire/**/*.php',
        './resources/views/livewire/**/*.blade.php',
    ],

    darkMode: 'class',

    safelist: [
        {
            // Comprehensive pattern for workflow navbar and general badge classes
            pattern: /(bg|text|ring)-(red|blue|indigo|green|purple|yellow|orange|teal|cyan|pink|gray)-(50|100|200|300|400|500|600|700|800|900)/,
        },
        // Explicit workflow navbar classes to ensure they're always included
        'bg-red-50', 'text-red-700', 'ring-red-200', 'bg-red-100', 'text-red-800',
        'bg-blue-50', 'text-blue-700', 'ring-blue-200', 'bg-blue-100', 'text-blue-800',
        'bg-indigo-50', 'text-indigo-700', 'ring-indigo-200', 'bg-indigo-100', 'text-indigo-800',
        'bg-green-50', 'text-green-700', 'ring-green-200', 'bg-green-100', 'text-green-800',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: 'var(--primary-50)',
                    100: 'var(--primary-100)',
                    200: 'var(--primary-200)',
                    300: 'var(--primary-300)',
                    400: 'var(--primary-400)',
                    500: 'var(--primary-500)',
                    600: 'var(--primary-600)',
                    700: 'var(--primary-700)',
                    800: 'var(--primary-800)',
                    900: 'var(--primary-900)',
                },
                secondary: {
                    50: 'var(--secondary-50)',
                    100: 'var(--secondary-100)',
                    200: 'var(--secondary-200)',
                    300: 'var(--secondary-300)',
                    400: 'var(--secondary-400)',
                    500: 'var(--secondary-500)',
                    600: 'var(--secondary-600)',
                    700: 'var(--secondary-700)',
                    800: 'var(--secondary-800)',
                    900: 'var(--secondary-900)',
                },
            },
        },
    },

    plugins: [forms],
};