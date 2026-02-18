import { defineConfig } from "vite";
import laravel, { refreshPaths } from "laravel-vite-plugin";
import { viteStaticCopy } from "vite-plugin-static-copy";
import tailwindcss from "@tailwindcss/vite";
// import { glob } from "glob";
import path from "path";

// Get all theme CSS and JS files dynamically from /themes root folder
// const themeAssets = [
// 	...glob.sync("themes/*/css/app.css"),
// 	...glob.sync("themes/*/js/app.js"),
// ];

export default defineConfig({
	plugins: [
		laravel({
			input: [
				"resources/css/app.css",
				"resources/js/app.js",
				"resources/css/filament/admin/theme.css",
				// ...themeAssets,
			],
			refresh: [
				...refreshPaths,
				"app/Filament/**",
				"app/Forms/Components/**",
				"app/Livewire/**",
				"app/Infolists/Components/**",
				"app/Providers/Filament/**",
				"app/Tables/Columns/**",
				"themes/**",
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
