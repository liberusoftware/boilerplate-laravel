import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'themes/liberu-base/resources/css/app.css',
                'themes/liberu-base/resources/js/app.js',
                'themes/default/resources/css/app.css',
                'themes/default/resources/js/app.js',
                'themes/dark/resources/css/app.css',
                'themes/dark/resources/js/app.js',
                'themes/clear-signal/resources/css/app.css',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                bunny('Inter', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
