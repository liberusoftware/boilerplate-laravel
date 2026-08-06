import { existsSync, readdirSync, readFileSync } from 'node:fs';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Each theme package declares its build entry points in theme.json; that manifest is
// the single source of truth, so installing a theme is enough to get it built.
const themeInputs = readdirSync('themes', { withFileTypes: true })
    .filter((entry) => entry.isDirectory() && existsSync(`themes/${entry.name}/theme.json`))
    .flatMap((entry) => {
        const { assets = {} } = JSON.parse(readFileSync(`themes/${entry.name}/theme.json`, 'utf8'));

        return [...(assets.css ?? []), ...(assets.js ?? [])].map((asset) => `themes/${entry.name}/${asset}`);
    });

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                ...themeInputs,
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
