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

### Module System (single — `app/Modules/`)
A custom `App\Modules\` system with lifecycle (install/enable/disable/uninstall, each firing
an event), a database registry (`modules` table via `App\Models\Module`), and `ModuleManager`.
Lives under the existing `App\` PSR-4 autoload — no per-module `composer.json`, no
merge-plugin, no `internachi/modular`/`app-modules/` (removed in the rebuild; there is no
`ExternalModuleLoader`). `ModuleManager` discovers modules by scanning `app/Modules/*` for a
`module.json` (framework subfolders like `Contracts/`, `Events/`, `Traits/` are skipped) and
resolving the main class as `App\Modules\{Dir}\{Dir}` or `…\{Dir}Module`. Modules extend
`BaseModule`, implement `ModuleInterface`, and can use the `Configurable` + `HasModuleHooks`
traits.

`app/Modules/Blog/` is the reference implementation — a real flagship module, not a fixture:
a `Post` model backed by the `module_blog_posts` migration, a `blog::index` view + `blog.index`
route + `BlogController`, `config('blog.*')`, and an admin `PostResource`. It ships enabled by
default (seeded in `DatabaseSeeder`). `ModuleServiceProvider` merges each module's own
`config/{module}.php` at that module's root config key (`config('blog.posts_per_page')`, not
the doubled-up `config('blog.blog.posts_per_page')`) and only registers a module's
routes/views/translations when its `modules` row is enabled — config and migrations always
load regardless of enabled state.

Filament components are discovered **per panel** by `App\Filament\Plugins\ModuleFilamentPlugin`,
registered on each panel via `->plugins([ModuleFilamentPlugin::make()->for('Admin')])` /
`->for('App')`: for each **enabled** module it scans `Filament/Admin/{Resources,Pages,Widgets}`
into the `/admin` panel and `Filament/App/{Resources,Pages,Widgets}` into the `/app` panel.
`ModuleResource` (`app/Filament/Resources/`) is the admin UI for the module registry itself
(`$model = null`, so it needs no tenant opt-out).

**Panel access policy:** `User::canAccessPanel()` requires a `super_admin` or `admin` role to
reach `/admin` (checked directly against the `model_has_roles` pivot across all teams, since
Shield's team-scoped context isn't reliably set at this point) — `/app` stays open to any
authenticated user. Per-resource Shield policies still gate individual resources within a panel.

### Real-Time Stack
- **Laravel Reverb** runs as a standalone WebSocket server.
- **Laravel Echo** (frontend, `resources/js/app.js`) connects via Pusher protocol over Reverb.
- Broadcasting channels are defined in `routes/channels.php`.
- **Horizon** manages Redis queues; dashboard at `/horizon`.
- **Octane + RoadRunner** provides the HTTP server in production (config in `.rr.yaml`).

### Permissions Model (Phase 1 — done)
Spatie Permission roles/permissions are team-scoped (`config/permission.php` `teams => true`, models point at `App\Models\Role`/`Permission`). `FilamentShield` auto-generates policies per Filament resource. Policy classes in `app/Policies/`.

**Filament tenancy rules (important):**
- The **admin** panel is Team-tenant-scoped (`->tenant(Team::class)`); the **app** panel is intentionally **not** scoped. `User`'s tenancy methods (`getTenants` = owned + member teams, `canAccessTenant`, `getDefaultTenant`) are panel-agnostic.
- Any Filament Resource whose model has **no `team()` relationship** MUST override `public static function isScopedToTenant(): bool { return false; }`, or the tenant panel **500s** on that resource. Shield's global `RoleResource` is handled via `FilamentShieldPlugin::make()->scopeToTenant(Utils::isTenancyEnabled())`.
- With `permission.teams=true`, roles carry a `team_id`, so `shield:generate` / super-admin assignment must run **inside a team context** (set `setPermissionsTeamId($team->id)` in a seeder, or scope the command). Default shield config has `super_admin.define_via_gate=false` — a bare `super_admin` role grants nothing until permissions are generated.
- `User` resolves the `HasRoles::teams` vs `HasTeams::teams` trait collision by keeping Jetstream's `teams()` (`insteadof`) and excluding Spatie's (Spatie scopes via the `team_id` column, not that relation).
- `User::canAccessPanel()` requires a `super_admin`/`admin` role for `/admin` (see Module System's panel access policy above); `/app` is open to any authenticated user. Per-resource Shield policies still gate each resource within a panel.

### Theme System
Themes live under `themes/{name}/` (each with `theme.json` + `css/`/`js/`/`views/`), discovered from disk by `ThemeManager` (`app/Services/ThemeManager.php`). Active theme resolves user `theme_preference` → session → `config('theme.default')`; the `ThemeSwitcher` Livewire component + `set_theme()` helper persist it. `ThemeServiceProvider` registers the `theme` singleton/alias, 4 Blade directives (`@themeAsset`, `@themeCss`, `@themeJs`, `@themeLayout`), and shares `$activeTheme`/`$themeConfig` with all views. Theme view overrides work via `View` finder `prependLocation` (a `layouts.app` reference resolves to the active theme's override).

**Per-theme Vite inputs are deferred** — `vite.config.js` builds only the main `app.css`/`app.js`. `@themeCss`/`@themeJs` gate on the Vite *manifest* (`ThemeManager::viteHasAsset`), so they emit nothing until `themes/*/{css,js}` are added to the Vite `input` and built — no 500. Wire those inputs in the PR that first ships a page extending a theme layout.

Site-wide theme is admin-selectable via `SiteSettings::$active_theme` (Appearance
section on the `ManageSiteSettings` page). Frontend resolution is
`user.theme_preference → session → SiteSettings.active_theme → config('theme.default')`;
each candidate is validated with `ThemeManager::themeExists()` before use, so a stale/invalid
preference falls through instead of erroring. Because `ThemeManager` is a boot-once singleton
(long-lived under Octane, reused within a test), the theme is **re-derived on every view
render** in `ThemeServiceProvider`'s `View::composer('*', ...)`, not just once at boot — so an
admin theme change (or a mid-lifecycle session/user pref) is picked up on the next render.
This applies to frontend Blade rendering only: Filament panels evaluate `->colors()` once at
worker boot, so under Octane a panel color change is only visible after a worker restart, not
the next request. Filament panels follow the **site-wide** theme only (no per-user panel theming):
`AdminPanelProvider`/`AppPanelProvider` call
`->colors(app(ThemeManager::class)->getFilamentColors(app(ThemeManager::class)->getSiteTheme()))`,
which maps a theme's `theme.json` `colors.primary` (a Tailwind color name) to a Filament
`Color` palette (unknown/missing → Amber). Compiled per-theme Filament CSS is **not** built
yet; the reserved hook is a `theme.json` `filament_css` key + `->viteTheme()` on the panel,
added when a theme needs its own Filament stylesheet.

**Frontend theme bundles:** each real theme may ship a self-contained Tailwind
bundle at `themes/<name>/css/app.css` wired into `vite.config.js` `input`. Blade
loads the active theme via the `@themeVite` directive, which calls
`ThemeManager::activeCssEntry()`: it returns the theme's bundle path when that
bundle is in the Vite manifest, otherwise `resources/css/app.css`. So `default`
(and `dark`) keep the stock `app.css` look, while `clear-signal` (teal, DESIGN.md
North Star; `colors.primary: teal` → Filament panels go `Color::Teal`) restyles
the blade frontend once built. A fresh install must run `npm run build` to compile
the `clear-signal` bundle; `default` stays active by default (zero visual change).

### Multi-Language (Phase 3 — done)
Supported locales live in `config('app.supported_locales')` (en/es/fr/de). `SetLocale` resolves locale (request param → session → `users.locale` → `Accept-Language` → default, validated against supported) and runs on the **`web` group** (`bootstrap/app.php`) **and both Filament panels** (added to each panel's `->middleware([])`, since Filament panels don't use the `web` group). Precedence is request > session > user (a stale session locale can shadow a freshly-logged-in user until logout flushes the session). `LanguageSwitcher` Livewire component persists to session + `users.locale`. `TranslationService` does on-demand translation via the MyMemory API (cached 30 days). No `locale_helpers`/`lang/*/messages` were ported (no callers); add `lang/` files only when something calls `__('...')`. The `LanguageSwitcher` component isn't mounted in any view yet — mount `<livewire:language-switcher />` where a switcher UI is wanted.

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
