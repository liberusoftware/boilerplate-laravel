import { defineConfig } from 'vite';
import { globSync, readFileSync } from 'node:fs';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

const themeAssets = globSync('themes/*/theme.json').flatMap((manifestPath) => {
    const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
    const themePath = manifestPath.slice(0, -'/theme.json'.length);

    return [...(manifest.assets?.css ?? []), ...(manifest.assets?.js ?? [])]
        .map((asset) => `${themePath}/${asset}`);
});

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                ...themeAssets,
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
