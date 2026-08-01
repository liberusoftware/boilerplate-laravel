# Repository guide

This is a Laravel 13 composition repository for independently published Liberu modules and themes. Treat the files on disk and package manifests as authoritative.

## Commands

```bash
composer install
npm install
php artisan test
XDEBUG_MODE=coverage php artisan test --coverage-clover=coverage.xml --min=100
vendor/bin/pint --test
npm run build
```

Each directory in `modules/` and `themes/` is also a standalone Composer package. From a package directory, run `composer install` followed by `vendor/bin/pest`.

## Application boundary

The host-specific application layer is deliberately small:

- `app/Models/User.php` composes authentication, team, authorization and profile contracts.
- `app/Filament/ModulePlugins.php` loads panel plugins declared by enabled module manifests.
- `app/Providers/Filament/` defines the admin and application panels.
- `bootstrap/app.php`, `routes/` and `config/` compose the installed packages.

Reusable code must live in its owning package and must not reference the `App\` namespace. `tests/Architecture/ModuleBoundariesTest.php` enforces that boundary across all module PHP files.

## Modules

Modules are `liberu-module` Composer packages under `modules/{name}`. Composer controls installation and autoloading; `module.json` controls runtime identity, dependencies, capabilities, default state and Filament presentation plugins. Packages intentionally omit `extra.laravel.providers`, so Laravel package auto-discovery cannot bypass the module manager.

`config/modules.php` classifies every installed module as enabled or disabled. Analytics Google, Analytics Meta and MyMemory localization are credential-gated adapters and are disabled until explicitly configured. Search Demo is a development-only package and is enabled only by the host test suite.

Domain packages remain independent of Filament. Presentation belongs in companion `*-filament` modules. Cross-package imports require a matching Composer dependency.

Every module contains its own Testbench/Pest setup, `phpunit.xml` and GitHub Actions workflow. Package integration tests extend the package-local `Tests\TestCase`, never the host `Tests\TestCase`.

## Themes

Themes are `liberu-theme` Composer packages under `themes/{name}`. Their `theme.json` files define parentage, compatibility, capabilities, assets and colors. `modules/theme-support` discovers and validates manifests, resolves inheritance, registers view paths and supplies the Blade directives.

Only `liberu-base` provides `resources/views/layouts/app.blade.php`; child themes inherit it through the view finder. Root Vite inputs are derived from each manifest's `assets.css` and `assets.js` entries. Per-theme Vite configuration files are not used.

Each theme has a standalone Testbench/Pest suite. Metadata tests validate its provider and assets; the host boundary suite validates the complete installed parent graph and renders the inherited layout.

## Authorization

Spatie Permission is team-scoped. `modules/roles-permissions` owns the team-agnostic role lookup used when no active team exists. `App\Models\User` delegates `hasAdminAccess()`, `isSuperAdmin()` and `isAdmin()` through that configured lookup. Filament, Telescope, Pulse and Horizon therefore use the same role and table configuration.

The admin panel is team-tenant-scoped; the application panel is not. Resource policies and module-owned permissions remain the final authorization boundary.

## Authentication and related services

Fortify supplies authentication primitives, Jetstream supplies teams and profiles, and Socialstream supplies OAuth connections. Social routes are registered by their installed packages and the host's existing route files; there is no `routes/socialstream.php`.

`laravel/passkeys` is installed through `modules/identity`; `User` does not carry a passkey trait. Reverb is installed but not wired to application behavior: `resources/js/app.js` is empty and no module currently broadcasts events.

## Testing and CI

The host runs application, composition, architecture and explicitly enumerated package suites. PHPUnit does not expand wildcards in `<directory>`, so `phpunit.xml` lists every module `tests` and `src` directory. Package-local `vendor/` directories and lockfiles are ignored and must never be swept into the host run.

The required host line and method coverage is 100%. Environment-dependent infrastructure checks may skip when their external service or extension is unavailable.

## Frontend and operations

Vite builds the host CSS/JS plus all manifest-declared theme assets. Filament panels resolve their plugins from enabled module manifests. Horizon handles queues, Octane can use RoadRunner, and Reverb remains available for a future broadcasting integration.

Before changing a documented path or namespace, verify it exists with `rg --files` and update the owning package documentation rather than describing hypothetical host classes.
