# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Shape of this repository

This is a **composition host**, not an application. `app/` holds exactly four files:

```
app/Models/User.php                            the one host model
app/Filament/ModulePlugins.php                 composes panel plugins from enabled modules
app/Providers/Filament/AdminPanelProvider.php
app/Providers/Filament/AppPanelProvider.php
```

Everything else — search, messaging, theming, localization, settings, authorization,
observability, blog — lives in **44 `liberu-module` packages** under `modules/` and
**4 `liberu-theme` packages** under `themes/`. Both directories are Composer install
targets *and* tracked in Git (`.gitignore` negates them explicitly).

Before assuming a class lives in `app/`, search `modules/`. Almost nothing is in `app/`.

### The publish loop

`modules/` and `themes/` are the source of truth. `scripts/publish-components` rsyncs each
`modules/<name>/` and `themes/<name>/` into its own GitHub repository (`--delete`, excluding
`.git`), which is then tagged and installed back through Composer from Packagist
(`liberusoftware/search`, `liberusoftware/theme-dark`, …).

Practical consequence: **edit packages here, in this monorepo.** A change is published by
committing it here and running `publish-components --push`. Packagist names drop the
`module-` prefix that the GitHub repositories carry.

## Commands

```bash
composer install && npm install

composer test                  # vendor/bin/pest
vendor/bin/pest tests/Feature/SearchTest.php
vendor/bin/pest --filter=SearchTest
vendor/bin/pint                # --test to check without writing

npm run dev                    # vite
npm run build                  # required once, to compile the theme bundles

php artisan migrate
php artisan migrate:fresh --seed

php artisan horizon
php artisan reverb:start
php artisan octane:start --server=roadrunner

php artisan filament:upgrade
php artisan shield:generate

# A single package's own suite, standalone:
cd modules/search && composer update && vendor/bin/pest
```

## Architecture

### Module system

`ModuleManagerServiceProvider` (first entry in `bootstrap/providers.php`) discovers packages
from `config('modules.paths')` and reads each `module.json` — provider class, capabilities,
required packages and capabilities, `default_enabled`, and any Filament plugins.
`ModuleRegistry::resolve($enabled, $disabled)` validates constraints with `Semver` and returns
providers in dependency order.

Installation never implies boot: no package declares `extra.laravel.providers`, so Laravel's
auto-discovery finds nothing to register, and an architecture rule asserts that stays true.
Enablement is a separate, explicit decision.

`config/modules.php` holds two lists. `$applicationModules` (41 names) is the default for
`MODULES_ENABLED`; `$optionalAdapters` (`analytics-google`, `analytics-meta`,
`localization-mymemory`) is the default for `MODULES_DISABLED` — they need third-party
credentials, so they ship installed but off. **Disabled beats enabled**, and either env var
replaces its whole list. Every directory under `modules/` must appear in one of the two,
enforced by `tests/Architecture/ModuleBoundariesTest.php`.

All 44 manifests declare `default_enabled: false`; the enabled list is the working lever.

Domain packages stay presentation-agnostic. Filament UI lives in companion `*-filament`
packages whose manifests declare `admin` and/or `app` plugin classes; `App\Filament\ModulePlugins`
instantiates only the plugins of enabled modules, rejecting duplicate ids and non-`Plugin` types.
Cross-package APIs go through declared Composer dependencies and, where an adapter needs
implementation independence, small contract packages (`liberusoftware/analytics-contracts`,
`liberusoftware/localization-contracts`). See `docs/MODULE_DEVELOPMENT.md`.

### Architecture tests

`tests/Architecture/ModuleBoundariesTest.php` holds 12 executable rules: package metadata
consistency, every module exercising its provider, no `App\` dependency from module `src/`,
no Filament in non-presentation modules, `-api` adapters not importing domain models, Composer
owning every autoload boundary, `-filament` modules declaring plugins, declared cross-package
namespace dependencies, enabled/disabled accounting for every installed module, `phpunit.xml`
running and measuring every module, theme parents resolving, and themes shipping the assets
they declare.

This is the cheapest place to enforce a boundary. Prefer adding a rule here over adding prose.

### Packages are standalone-testable

Every package carries `require-dev` (`orchestra/testbench ^11.1`, `pestphp/pest ^5.0`),
`autoload-dev` (`<PsrRoot>\Tests\` → `tests/`), its own `phpunit.xml`, and its own
`.github/workflows/tests.yml` — which `publish-components` carries upstream, so the split
repositories get real CI. `tests/Integration/ServiceProviderTest.php` boots
`Orchestra\Testbench\TestCase`, never the host's `Tests\TestCase`.

A test belongs **in the package** only if it needs nothing from the host. Several suites that
look package-shaped are not: the `ThemeManager*` tests assert on the host's `themes/` directory,
and `SearchServiceTest`/`SetLocaleMiddlewareTest` use `App\Models\User`. Those are composition
tests and stay in `tests/`.

### Dual Filament panels

- **`AdminPanelProvider`** — `->default()`, path `/admin`, tenant-scoped to
  `Liberu\Foundation\Organizations\Models\Team` (`ownershipRelationship: 'team'`), with Shield's
  `SyncShieldTenant` as persistent tenant middleware.
- **`AppPanelProvider`** — path `/app`, deliberately **not** tenant-scoped.

Both resolve colours through `app(ThemeColors::class)->forSite()` and append `SetLocale` +
`SecurityHeaders` to `->middleware([])`, because Filament panels don't run the `web` group.
Both take `->plugins(app(ModulePlugins::class)->forPanel(...))`.

`routes/web.php` is two routes: `/` renders `welcome`, and `/dashboard` redirects a super admin
into the admin panel at their default tenant, and everyone else to `filament.app.pages.dashboard`.

### Authentication and permissions

Fortify provides the primitives, Jetstream adds teams and profile management,
`bursteri/socialstream` adds OAuth, Spatie Permission provides team-scoped roles
(`permission.teams => true`, models pointing at `Liberu\Foundation\Authorization\Models\Role`
and `Permission`).

`User` implements seven contracts from four packages — `PrivilegedActor` (`isSuperAdmin`),
`ObservabilityActor` (`isAdmin`), `OrganizationActor`, `ConnectedAccountOwner`, plus Filament's
`FilamentUser`, `HasTenants` and `HasDefaultTenant`. Its three role checks route through one
private `hasRoleInAnyTeam(...$names)` helper that reads table names from `permission.table_names`
and the super admin name from `filament-shield.super_admin.name`. It queries the pivot directly
and team-agnostically, because Spatie's `hasRole()` is bound to an active team context that
isn't set when `canAccessPanel()` or the Telescope/Pulse gates run.

**Tenancy rules that bite:**

- Any Filament Resource whose model has no `team()` relationship MUST override
  `isScopedToTenant(): bool { return false; }`, or the tenant panel 500s on it. The authorization
  presentation module wraps Shield with tenant resource scoping disabled, because Spatie already
  isolates roles by `team_id`.
- With `permission.teams=true` roles carry a `team_id`, so `shield:generate` and super-admin
  assignment must run **inside a team context** (`setPermissionsTeamId($team->id)`).
- `User` resolves the `HasRoles::teams` / `HasTeams::teams` collision with
  `HasTeams::teams insteadof HasRoles` — Jetstream's relation wins; Spatie scopes by column.
- `canAccessPanel()` requires `super_admin` or `admin` for `/admin`; `/app` is open to any
  authenticated user. Per-resource Shield policies still gate each resource within a panel.
- `config/filament-shield.php` `tenant_model` and `config/permission.php` `models.team` are both
  **deliberately `null`** — each config carries a comment explaining why. Do not "fix" them.

Passkeys are `laravel/passkeys`; the table migration lives in the `identity` module. `User` does
not use a passkey trait.

### Themes

Themes are packages under `themes/`, each with `theme.json` (name, version, provider, `type`,
`parent`, `assets`) and a `resources/` tree. `liberu-base` is the `shared` root; `default`, `dark`
and `clear-signal` are `public` themes declaring `parent: liberu-base`.
`ThemeManager::inheritanceChain()` walks the chain with a cycle guard, and only `liberu-base`
ships `layouts/app.blade.php`, so every public theme renders through the fallback.

`config/theme.php` holds `default`, `fallback`, per-surface themes (`public` → `clear-signal`),
optional caching and asset budgets. Site-wide selection lives in
`Liberu\Foundation\Settings\Settings\SiteSettings::$active_theme`, edited from
`Liberu\Foundation\SettingsFilament\Pages\ManageSiteSettings`.

Filament panels follow the **site-wide** theme only, via `ThemeColors::forSite()` mapping a
theme's `colors.primary` to a Filament palette (unknown → Amber). Panels evaluate `->colors()`
once at worker boot, so under Octane a colour change needs a worker restart.

**Vite inputs are derived, not listed.** `vite.config.js` reads every `themes/*/theme.json` and
flattens `assets.css` + `assets.js`. Installing a theme package is enough to get it built; a
typo in `assets` fails the build, which is why an architecture rule checks those files exist.
A fresh install must run `npm run build`.

### Multi-language

`config('app.supported_locales')` (en/es/fr/de).
`Liberu\Foundation\Localization\Http\Middleware\SetLocale` resolves request param → session →
`users.locale` → `Accept-Language` → default, and runs on the `web` group (`bootstrap/app.php`)
**and** both panels. Precedence is request > session > user, so a stale session locale can shadow
a freshly logged-in user until the session is flushed.

`LanguageSwitcher` lives in `localization-livewire`; mount `<livewire:language-switcher />` where
a switcher is wanted — it isn't mounted anywhere yet. On-demand machine translation is
`localization-mymemory`, **disabled by default** (needs the MyMemory API).

### Operations

Horizon manages Redis queues at `/horizon`. Octane + RoadRunner serves HTTP in production
(`.rr.yaml`). Telescope (dev) and Pulse are gated by `ObservabilityActor::isAdmin()` from the
`observability` module. `bootstrap/app.php` appends `SetLocale` and `SecurityHeaders` to the
`web` group, registers a `/up` health route, and renders JSON exceptions for `api/*`.

**Reverb is installed but not wired.** `resources/js/app.js` is empty, there is no Echo client,
and `routes/channels.php` holds only Laravel's default user channel — no module broadcasts.
Treat real-time as an available dependency, not a working feature.

## Testing

Pest 5 / PHPUnit 13, SQLite in-memory (env overrides in `phpunit.xml`; no `.env.testing`
database config needed). Base class `tests/TestCase.php`, Pest config `tests/Pest.php`
(`Feature` gets `RefreshDatabase`).

Five testsuites: `Unit`, `Feature`, `Architecture`, `Modules`, `Themes`. The `Modules` and
`Themes` suites list each package's `tests` directory explicitly rather than scanning — PHPUnit
does **not** expand wildcards in `<directory>` or `<exclude>`, and a package whose standalone
suite has been run locally has a `vendor/` that must not be swept in. The same applies to the
`<source>` coverage list. Both lists are guarded by an architecture rule, so neither can drift
from what is installed.

Note that most of `tests/Unit` is integration-shaped: it boots the app, reads host config and
asserts across several packages. Only tests needing nothing from the host belong in a package.

## Known upgrade blockers

- **`spatie/laravel-permission` → v8**: blocked by `bezhansalleh/filament-shield ~4.x` requiring
  `^6.0|^7.0`. Upgrade both together once Shield releases v5+ with v8 support.
- **`spiral/roadrunner-http` → v4**: blocked by `laravel/octane ^2.x`; v4 has breaking API
  changes Octane doesn't support.

## PHP 8.5 notes

- `PDO::MYSQL_ATTR_SSL_CA` is deprecated; `config/database.php` uses `Pdo\Mysql::ATTR_SSL_CA`.

## Agent skills

### Issue tracker

Issues live in GitHub Issues on `liberusoftware/boilerplate-laravel`, driven by the `gh` CLI.
See `docs/agents/issue-tracker.md`.

### Triage labels

The five canonical roles, each label string equal to its name (`needs-triage`, `needs-info`,
`ready-for-agent`, `ready-for-human`, `wontfix`). See `docs/agents/triage-labels.md`.

### Domain docs

Single-context — one root `CONTEXT.md` and one `docs/adr/` covering the host plus every
`modules/` and `themes/` package. See `docs/agents/domain.md`.

### Handoffs

Session handoff documents go in **`docs/handoffs/`**, named `YYYY-MM-DD-<topic>.md` — never in
`/tmp` or any OS temp directory. WSL's `/tmp` is wiped on restart and is invisible to everyone
else, so a handoff written there is lost precisely when the next session needs it.

This overrides the `/handoff` skill's default of writing to the OS temp directory.

A handoff references artifacts rather than restating them — link the spec, ADR, issue or commit
by path or URL. What belongs in it is only what those artifacts cannot hold: environment state,
traps that cost time, uncommitted work, and what the next session should do.
