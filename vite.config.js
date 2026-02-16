import { defineConfig } from "vite";
import laravel, { refreshPaths } from "laravel-vite-plugin";
import { viteStaticCopy } from "vite-plugin-static-copy";
import tailwindcss from "@tailwindcss/vite";
import { glob } from "glob";
import path from "path";

// Get all theme CSS and JS files dynamically
const themeAssets = [
	...glob.sync("resources/css/themes/*/app.css"),
	...glob.sync("resources/js/themes/*/app.js"),
];

export default defineConfig({
	plugins: [
		laravel({
			input: [
				"resources/css/app.css",
				"resources/js/app.js",
				"resources/css/filament/admin/theme.css",
				...themeAssets,
			],
			refresh: [
				...refreshPaths,
				"app/Filament/**",
				"app/Forms/Components/**",
				"app/Livewire/**",
				"app/Infolists/Components/**",
				"app/Providers/Filament/**",
				"app/Tables/Columns/**",
			],
		}),
		viteStaticCopy({
			targets: [
				{
					src: "resources/images/*",
					dest: "images",
				},
			],
		}),
		tailwindcss(),
	],
});
