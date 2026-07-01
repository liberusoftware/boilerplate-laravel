# Module Development Guide

This app has one module system: the custom `App\Modules\` framework rooted at
`app/Modules/`. There is no `internachi/modular`, no per-module `composer.json`, no
composer-merge-plugin, and no `app-modules/` directory — none of that is used or supported.

The worked example throughout this guide is `app/Modules/Blog/`, the reference module
shipped with the app.

## Anatomy

```
app/Modules/Blog/
├── module.json                     # name, version, description, dependencies, config
├── BlogModule.php                  # main module class, extends BaseModule
├── Filament/
│   └── Admin/                      # discovered into the /admin panel
│       └── Resources/
│           ├── PostResource.php
│           └── PostResource/Pages/{ListPosts,CreatePost,EditPost}.php
├── Http/Controllers/BlogController.php
├── Models/Post.php
├── config/blog.php
├── database/migrations/2026_07_01_000000_create_module_blog_posts_table.php
├── resources/views/index.blade.php
└── routes/web.php
```

`ModuleManager` only recognizes a directory under `app/Modules/` as a module if it contains
a `module.json` — this is how framework-internal subfolders (`Contracts/`, `Events/`,
`Traits/`) are skipped during discovery. The module's main class must resolve as either
`App\Modules\{Dir}\{Dir}` (e.g. `App\Modules\Blog\BlogModule`) or `App\Modules\{Dir}\{Dir}Module`.

`module.json` fields:

```json
{
    "name": "Blog",
    "version": "1.0.0",
    "description": "...",
    "dependencies": [],
    "config": { "posts_per_page": 10, "allow_comments": false }
}
```

`name`/`version`/`description`/`dependencies`/`config` are read by `BaseModule::loadModuleInfo()`
and persisted to the `modules` DB table (`App\Models\Module`) the first time the module is
discovered.

## Adding a Filament resource to a panel

Filament components are auto-discovered **per panel** by
`App\Filament\Plugins\ModuleFilamentPlugin`, which each panel registers as a plugin:

```php
// AdminPanelProvider
->plugins([
    // ...
    ModuleFilamentPlugin::make()->for('Admin'),
])

// AppPanelProvider
->plugins([
    // ...
    ModuleFilamentPlugin::make()->for('App'),
])
```

For every **enabled** module, the plugin scans:

- `Filament/Admin/Resources` / `Pages` / `Widgets` → registered into the `/admin` panel
- `Filament/App/Resources` / `Pages` / `Widgets` → registered into the `/app` panel

Namespaces follow the folder: a resource at `app/Modules/Blog/Filament/Admin/Resources/PostResource.php`
lives in `App\Modules\Blog\Filament\Admin\Resources`. Blog only ships an `Admin` resource
(`PostResource`); add a `Filament/App/...` sibling if a module needs app-panel components too.

If your resource's model has no `team()` relationship, override
`isScopedToTenant(): bool { return false; }` — the `/admin` panel is tenant-scoped to `Team`
and will 500 on a non-team-scoped resource otherwise (see `PostResource` for the pattern).

## Routes, views, config, migrations

`ModuleServiceProvider` wires these up for every module found under `app/Modules/*`:

- **Routes** — `routes/web.php` / `api.php` / `admin.php`, loaded with `loadRoutesFrom` (no
  route-group prefix or middleware is applied automatically — declare that in the file itself,
  e.g. `routes/web.php`'s `Route::get('/blog', ...)->name('blog.index')`).
- **Views** — `resources/views/` loaded under a namespace derived from `Str::snake($moduleName)`,
  so Blog's views resolve as `blog::index`.
- **Translations** — `resources/lang/` loaded the same way, if present.
- **Config** — `config/*.php` is merged for every module regardless of enabled state. A file
  named after the module itself merges at the module's own root config key: Blog's
  `config/blog.php` becomes `config('blog.posts_per_page')`, not the doubled-up
  `config('blog.blog.posts_per_page')`. Any other filename merges under `{module}.{file}`.
- **Migrations** — `database/migrations/` is always loaded via `loadMigrationsFrom`, so
  `php artisan migrate` picks up module migrations regardless of enabled state.

**Routes, views, and translations are gated by enabled state** — config and migrations are not
(see below).

## Lifecycle

Modules extend `BaseModule` (implements `ModuleInterface`) and can use the `Configurable` +
`HasModuleHooks` traits. Override the hook methods for custom logic:

```php
class BlogModule extends BaseModule
{
    protected function onInstall(): void { /* ... */ }
    protected function onEnable(): void { /* ... */ }
    protected function onDisable(): void { /* ... */ }
    protected function onUninstall(): void { /* ... */ }
}
```

`ModuleManager` drives the lifecycle:

- `install($name)` — checks dependencies, runs the module's migrations, publishes assets,
  calls `onInstall()`, then enables the module. Dispatches `ModuleInstalled`.
- `enable($name)` / `disable($name)` — check dependencies / dependents, run `onEnable()` /
  `onDisable()`, persist `enabled` on the `modules` row. Dispatch `ModuleEnabled` / `ModuleDisabled`.
- `uninstall($name)` — disables the module, rolls back its migrations, removes published
  assets, calls `onUninstall()`. Dispatches `ModuleUninstalled`.

A module declaring `dependencies` in `module.json` can't be enabled/installed unless every
dependency is itself present and enabled, and can't be disabled/uninstalled while another
enabled module depends on it.

The admin UI for this is the `Modules` Filament resource (`app/Filament/Resources/ModuleResource.php`,
`/admin` → Modules): it lists every discovered module with enable/disable/install/uninstall
row actions, backed by `ModuleManager`. There is no artisan `module:*` command.

## Enabled-gating

Enabled state lives in the `modules` DB table (`enabled` column, `false` by default). Two
different defaults apply depending on which side reads it:

- `ModuleServiceProvider` and `ModuleFilamentPlugin` **default to enabled** when no `modules`
  row exists yet for a module — so a freshly added module's config/routes/Filament components
  work before the registry has been seeded or `ModuleManager` has run a discovery pass.
- `BaseModule::isEnabled()` (used by `ModuleManager`) has no such fail-open default — it
  reflects the persisted DB row, or `module.json`'s `config.enabled` if no row exists yet.

If a module should ship enabled out of the box, seed it explicitly — Blog's `DatabaseSeeder`
entry does this:

```php
Module::firstOrCreate(['name' => 'Blog'], [
    'enabled' => true,
    'version' => '1.0.0',
    'description' => '...',
]);
```

Once enabled: routes, views, and translations load; the module's Filament components are
discovered into the panel(s) matching their `Filament/Admin`/`Filament/App` subfolder. Config
and migrations load either way.

## Testing

See `tests/Unit/ModuleManagerTest.php` and `tests/Feature/ModuleDiscoveryTest.php` for
coverage of discovery, enable/disable persistence, and lifecycle events — useful as a template
when adding tests for a new module.
