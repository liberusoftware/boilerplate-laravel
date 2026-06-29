# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Current State — fresh rebuild in progress (2026-06-29)

This repo was **rebuilt from a fresh Laravel 13 skeleton** on branch `chore/fresh-skeleton`.
Design + roadmap: `docs/superpowers/specs/2026-06-29-fresh-skeleton-design.md`.

**Phase 0 (done, green):** fresh skeleton + full package set (Socialstream/Jetstream +
teams, dual Filament v5 panels + Shield, Spatie permission/media-library/backup/activitylog/
query-builder, Reverb, Horizon, Octane/RoadRunner, Telescope, Pulse, passkeys, menu-builder).
Authenticated home = the Filament **app** panel via the `dashboard` route. OAuth: GitHub,
Google, Facebook, Twitter(OAuth2) enabled (placeholder creds in `.env`).

**Single module system:** `app/Modules/` only — `internachi/modular` and
`composer-merge-plugin` were **removed**. There is no `app-modules/` layer.

**NOT yet ported (Phases 1–7, each its own PR):** custom `App\Modules` lifecycle +
`BlogModule`, `ThemeManager`/themes, multi-language (`SetLocale`/`TranslationService`),
`SearchService`, `SiteSettings`, messaging/chat. The architecture sections below describe
the **target** for those ports, not all of which exists yet — verify a class exists before
relying on it.

## Commands

```bash
# Install dependencies
composer install
npm install

# Run all tests
composer test          # runs vendor/bin/pest
php artisan test       # alternative

# Run a single test file
vendor/bin/pest tests/Feature/SearchTest.php

# Run tests by filter
vendor/bin/pest --filter=SearchTest

# Code style (Laravel Pint)
vendor/bin/pint
vendor/bin/pint --test   # check only, no changes

# Build frontend assets
npm run dev              # Vite dev server with HMR
npm run build            # production build

# Database
php artisan migrate
php artisan migrate:fresh --seed

# Queue / real-time
php artisan horizon      # Horizon queue worker UI at /horizon
php artisan reverb:start # WebSocket server (Laravel Reverb)
php artisan octane:start --server=roadrunner  # high-performance HTTP server

# Filament
php artisan filament:upgrade   # run after Filament updates
php artisan shield:generate    # regenerate Filament Shield permissions
```

## Architecture

### Dual Filament Panel Setup
There are two Filament panels configured in `app/Providers/Filament/`:
- **AdminPanelProvider** — path `/admin`, tenant-scoped to `Team`, uses `FilamentShield` for role-based access, `FilamentMenuBuilderPlugin` for menus. Login delegates to Fortify's `AuthenticatedSessionController`.
- **AppPanelProvider** — user-facing Filament panel (profile, team management, API tokens).

Both panels disable default Fortify/Jetstream route registration in their `boot()` methods; routes are registered from `routes/web.php` and `routes/socialstream.php` instead.

### Authentication Stack (layered)
Fortify handles the authentication primitives, Jetstream adds teams and profile management, Socialstream extends with OAuth providers, and Spatie Permission provides role/permission assignment. The `TeamsPermission` middleware syncs the active team with Filament Shield's tenant context. The `AssignDefaultTeam` middleware ensures every user lands on a team after login.

### Module System (single — `app/Modules/`, target for Phase 4)
One mechanism: a custom `App\Modules\` system with lifecycle (install/enable/disable/
uninstall events), a database registry (`modules` table), and `ModuleManager`. Lives under
the existing `App\` PSR-4 autoload — no per-module `composer.json`, no merge-plugin. The
`BlogModule` under `app/Modules/BlogModule/` is the reference implementation. Modules hook
into each other via `HasModuleHooks`; discovery via `ExternalModuleLoader`.

`internachi/modular` / `app-modules/` are **not** used (removed in the rebuild). This system
is **not yet ported** — it is Phase 4 work.

### Real-Time Stack
- **Laravel Reverb** runs as a standalone WebSocket server.
- **Laravel Echo** (frontend, `resources/js/app.js`) connects via Pusher protocol over Reverb.
- Broadcasting channels are defined in `routes/channels.php`.
- **Horizon** manages Redis queues; dashboard at `/horizon`.
- **Octane + RoadRunner** provides the HTTP server in production (config in `.rr.yaml`).

### Permissions Model
Spatie Permission roles/permissions are team-scoped (multi-tenancy). `FilamentShield` auto-generates policies for each Filament resource. The `Permission` and `Role` models extend Spatie's defaults. Policy classes are in `app/Policies/`.

### Theme System
Themes live under `themes/{name}/` with their own CSS/JS/views. The active theme is resolved by `ThemeManager` (`app/Services/ThemeManager.php`) and set per user via `theme_preference` on the users table. Blade directives and helpers are registered in `ThemeServiceProvider`. Vite is configured to build per-theme assets (currently commented out pending re-enablement via `glob`).

### Multi-Language
User locale is stored in `users.locale`. The `SetLocale` middleware applies it on every request. `TranslationService` provides programmatic translation. Language files are in `lang/{locale}/`. A `LanguageSwitcher` Livewire component handles runtime switching.

### Search
`SearchService` (`app/Services/SearchService.php`) performs cross-entity full-text search over Users, Posts, and Groups. Dedicated API controllers under `app/Http/Controllers/Api/` serve search results. Search indexes are added via migration `2026_02_14_000003_add_search_indexes_to_users_table.php`.

### Site Settings
`SiteSettings` uses the `spatie/laravel-settings` pattern: a typed settings class at `app/Settings/SiteSettings.php`, stored in the `settings` table. Accessed via `App\Facades\SiteSettings` or injected directly. A Filament page (`ManageSiteSettings`) provides the admin UI.

### Frontend Build
Vite entry points (`vite.config.js`):
- `resources/css/app.css` + `resources/js/app.js` — main app
- `resources/css/filament/admin/theme.css` — Filament custom theme (Tailwind CSS v4 via `@tailwindcss/vite`)

HMR refresh is wired to all `app/Filament/**`, `app/Livewire/**`, and `themes/**` paths.

## Testing

PHPUnit 13 / Pest 5. Tests use SQLite in-memory (configured in `phpunit.xml`). The test database is configured via `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` env overrides; no separate `.env.testing` database config is needed.

Base test class: `tests/TestCase.php`. Pest config: `tests/Pest.php`.

Feature tests cover: chat, messaging, notifications, search (users/posts/groups/all), profile photos, site settings.
Unit tests cover: module system (loader, config, hooks, manager, model), locale middleware, team model, theme manager, translation service, user model.

## Known Upgrade Blockers

These upgrades are blocked by dependency conflicts — revisit when the blocking package releases support:

- **`spatie/laravel-permission` → v8**: Blocked by `bezhansalleh/filament-shield ~4.x` requiring `^6.0|^7.0`. Upgrade both together once filament-shield releases v5+ with v8 support.
- **`phpunit/phpunit` → 13.1.13+**: Blocked by `pestphp/pest 5.x-dev` explicitly conflicting with `phpunit > 13.1.8`. Will resolve when Pest 5 stable releases.
- **`spiral/roadrunner-http` → v4**: Blocked by `laravel/octane ^2.x` suggesting `^3.3.0`; v4 has breaking API changes unsupported by current Octane.

## Known PHP 8.5 Changes Applied

- `PDO::MYSQL_ATTR_SSL_CA` deprecated → replaced with `Pdo\Mysql::ATTR_SSL_CA` in `config/database.php`
- `Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys` changed from a trait to an interface; `User` now implements the interface and uses `InteractsWithPasskeys` trait
