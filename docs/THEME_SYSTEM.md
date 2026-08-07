# Theme system guide

The canonical implementation and release guide is [Theme architecture](THEME_ARCHITECTURE.md).

Themes are Composer packages in `/themes/{name}`. Each package declares `type: liberu-theme`, a stable installer name, a complete `theme.json`, a provider, compatibility metadata and canonical assets below `resources/`. The application discovers packages, validates capabilities and inheritance, and safely falls back to the configured default.

## Use

```php
set_theme('dark');
$current = active_theme();
```

```blade
@themeVite
<livewire:theme-switcher />
```

`@themeVite` selects declared, built entries and uses the application bundle when unavailable. `@themeAsset`, `@themeCss`, `@themeJs` and `@themeLayout` remain available for explicit composition.

## Create a package

Start with this minimum tree:

```text
themes/example/
├── composer.json
├── theme.json
├── README.md
├── CHANGELOG.md
├── resources/
│   ├── css/app.css
│   ├── js/app.js
│   └── views/
├── src/ExampleThemeServiceProvider.php
├── phpunit.xml
├── tests/
└── .github/workflows/tests.yml
```

Inherit `base` unless building a shared parent. Consume semantic tokens, use translations and logical CSS properties, keep JavaScript progressive and CSP-safe, and never implement module workflows in a theme.

The consuming application does not need a build change: its `vite.config.js` reads every
`themes/*/theme.json` and builds whatever `assets.css` and `assets.js` declare. Declare the
entries there, run `npm run build`, then validate:

```bash
php artisan theme:validate
php artisan theme:cache
npm run build
```

Release from the independent theme repository. Consumers update Composer constraints and lockfile, reinstall, review the tracked `/themes` diff, and rerun accessibility, visual, security and performance gates.
