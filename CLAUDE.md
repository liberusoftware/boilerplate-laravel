# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Shape of this repository

This is a **composition host**, not an application. `app/` holds exactly five files:

```
app/Models/User.php                            the one host model
app/Filament/ModulePlugins.php                 composes panel plugins from enabled modules
app/Support/ThemeColors.php                    maps the site theme to a Filament palette
app/Providers/Filament/AdminPanelProvider.php
app/Providers/Filament/AppPanelProvider.php
```

Everything else — search, theming, localization, settings, roles and permissions,
observability — lives in **40 `liberu-module` packages** under `modules/` and
**4 `liberu-theme` packages** under `themes/`. Both directories are Composer install
targets *and* tracked in Git (`.gitignore` negates them explicitly).

Before assuming a class lives in `app/`, search `modules/`. Almost nothing is in `app/`.

### Where a package is edited

**Each package's own repository is the source of truth, and `modules/`, `themes/` are Composer
output.** A change made directly under `modules/<name>/` survives exactly until the next
`composer update`, which fetches from the remote and overwrites it.

So a package is edited in a **clone**, at `~/code/<repo>` — cloned when needed rather than kept as a
permanent 44-repo workspace. `scripts/fleet` drives that:

```bash
scripts/fleet status                          # what is checked out, dirty, unpushed
scripts/fleet clone --only search             # ensure a repository is present
scripts/fleet run 'vendor/bin/pest'           # fan a command across all of them
scripts/fleet commit -m 'Fix the thing'       # stage, commit, push main
scripts/fleet tag 1.2.0 --only search         # explicit list required; humans tag
```

**The runner deliberately stops short of tagging.** A push is recoverable; a tag is what Packagist
publishes and what `ModuleValidator` pins the host to, so an unattended bad wave would be 44
revert-tags. `tag` also refuses a dirty worktree, a non-`main` branch, or unpushed commits.

A wave lands in the host with one commit: `composer update`, then `modules/` and `composer.lock`
together. `modules/` stays **tracked**, and CI fails on an uncommitted diff — that check is what
caught 48-of-48 divergence between the tracked tree and the published packages.

Packagist names drop the `module-` prefix that the GitHub repositories carry:
`liberusoftware/module-search` on GitHub is `liberusoftware/search` on Packagist.

> `scripts/publish-components` rsynced this monorepo *into* the package repositories, which is
> the opposite direction. It was **removed** once the coverage-ratchet wave proved a fleet-wide
> change could be released through `fleet` alone — see `docs/CONFORMANCE.md` §5, step 9.

## Commands

```bash
composer install && npm install

composer test                  # vendor/bin/pest
vendor/bin/pest tests/Feature/SearchTest.php
vendor/bin/pest --filter=SearchTest
vendor/bin/pint                # --test to check without writing
vendor/bin/phpstan analyse     # app/ only, at the level app/ passes

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

**A manifest's own `default_enabled` is what boots it** — true for 37 of the 40, false for
`analytics-google`, `analytics-meta` and `localization-mymemory`, which need third-party
credentials and so ship installed but off. `config/modules.php` names no modules at all; it holds
only `MODULES_ENABLED` and `MODULES_DISABLED`, both empty by default. `MODULES_ENABLED` adds
modules their manifests leave off, `MODULES_DISABLED` removes modules their manifests turn on, and
**disabled beats both**. Adding a module to a composition is therefore installing it; there is no
second list to remember.

Disabling a module something else requires is a `DependencyResolutionFailed`, not a quiet omission.
Two architecture rules pin all of this, including that config stays list-free.

Domain packages stay presentation-agnostic. Filament UI lives in companion `*-filament`
packages whose manifests declare `admin` and/or `app` plugin classes; `App\Filament\ModulePlugins`
instantiates only the plugins of enabled modules, rejecting duplicate ids and non-`Plugin` types.
Cross-package APIs go through declared Composer dependencies and, where an adapter needs
implementation independence, small contract packages (`liberusoftware/analytics-contracts`,
`liberusoftware/localization-contracts`). See `docs/MODULE_DEVELOPMENT.md`.

### Architecture tests

`tests/Architecture/ModuleBoundariesTest.php` holds 8 executable rules, and they are all
**whole-graph** — each reads every package at once, so no single package could run it: every
package installing standalone, enablement deriving from manifests, both env overrides behaving,
theme parents resolving, Composer owning every autoload boundary, one Composer vendor across the
fleet, every declared Filament plugin class existing, and declared cross-package namespace
dependencies.

Seven rules a package can check about *itself* moved to `liberusoftware/package-testbench`'s
boundary suites, which every repository runs against its own files (§3.8): package metadata,
provider registration, no `App\` dependency, no Filament in non-presentation modules, `-api`
adapters not importing domain models, `-filament` modules declaring plugins, themes shipping their
declared assets. A host rule could only ever fail in the host, which is not where the fault is.

Before adding a rule here, ask whether the owning package could run it alone. If it could, it
belongs in the testbench.

**Rules that filter on the vendor prefix derive it** from the package's own name via
`packageVendor()`. A hardcoded `liberusoftware/` would assert the spelling rather than the boundary,
and passes or fails 43 packages depending on which vendor §3.2 settled on.

Two things deliberately *not* asserted here, because a rule that cannot fire reads as coverage it
does not provide:

- **Plugin id uniqueness.** `ModulePlugins` rejects a duplicate while composing the panels, which
  happens when this suite boots the app — so the guard always fires first. It is covered in
  `tests/Unit/ModuleFilamentPluginsTest.php` instead, where it can actually fail.
- **A renamed package.** `ModuleValidationGuard` fails the boot with "Composer package is not
  installed" before any rule body runs.

This is the cheapest place to enforce a boundary. Prefer adding a rule here over adding prose — but
check the rule can be the thing that catches the fault, not the second thing to notice it.

### Packages are standalone-testable

Every package carries `require-dev` (`liberusoftware/package-testbench ^1.5`, `pestphp/pest ^5.0`),
its own `phpunit.xml`, and its own `.github/workflows/tests.yml` — a thin caller of the reusable
workflows in `liberusoftware/.github`, so every repository gets real CI without carrying a copy of
the workflow body.

**No package ships a test bootstrap or boundary tests of its own.** Its `phpunit.xml` declares a
`Boundary` testsuite pointing into
`vendor/liberusoftware/package-testbench/tests/Boundary/{Module,Theme,Contract}`, so a new boundary
rule is a testbench release every repository picks up rather than a 44-repository sweep. Opting out
of a rule means editing that one file. `Liberu\PackageTestbench\PackageTestCase` boots Testbench and
registers the provider the manifest names, so `getPackageProviders()` overrides are gone too.

It also registers the providers of the package's **dependencies**, because Testbench runs no package
discovery: `extra.laravel.providers` of any direct requirement, plus the manifest provider of a
sibling module in `require-dev`. Sibling modules in `require` are deliberately *not* booted — their
manifests declare that array empty precisely so installing one never boots it.

Practical consequence: a package whose provider needs a peer bound — `localization-mymemory` needs
something to bind `TranslationProviderRegistry` — declares that peer in `require-dev`. That keeps
the adapter free of the implementation at runtime while letting its own suite boot a real one.

Anything a package still needs of its own (`tests/Unit`, and the `autoload-dev` mapping when a test
is namespaced) stays, bound by a two-line `tests/Pest.php`.

**`tests/.gitkeep` is load-bearing.** Git does not track an empty directory, so a package with no
tests of its own would publish without `tests/` at all — and Pest aborts before any suite runs with
`The test directory [tests] does not exist`. 34 packages shipped that way once.

A test that needs an authenticated user uses the testbench's `TestUser` and the `UsesTestUser` trait,
which loads the base `users` migration and brings `RefreshDatabase`. That table is the one thing no
package owns — `profiles` adds `locale`, `theme_preference` and `timezone` to it, `search` adds its
indexes. `TestUser` implements none of the fleet's actor contracts; a package needing one subclasses
it in its own tests.

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
(`permission.teams => true`, models pointing at `Liberu\Foundation\RolesPermissions\Models\Role`
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
`parent`, `assets`) and a `resources/` tree. `base` is the `shared` root; `default`, `dark`
and `clear-signal` are `public` themes declaring `parent: base`.
`ThemeManager::inheritanceChain()` walks the chain with a cycle guard, and only `base`
ships `layouts/app.blade.php`, so every public theme renders through the fallback.

**Discovery is Composer-driven, like modules.** `ThemeDiscovery` merges the tracked `themes/` tree
with `InstalledVersions::getInstalledPackagesByType('liberu-theme')`, deduping by `realpath` so a
theme present both ways is one theme. A theme installed anywhere in `vendor/` is found, and a
composition with no `themes/` directory discovers nothing rather than throwing.

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

`LanguageSwitcher` lives in `localization-core-livewire`; mount `<livewire:language-switcher />` where
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

Three testsuites: `Unit`, `Feature`, `Architecture`. **The host measures the host** — coverage
`<source>` is `app` alone, and package tests are not run from here. Each package runs and measures
itself against its own `phpunit.xml`, standalone:

```bash
cd modules/search && composer update && vendor/bin/pest
```

There were `Modules` and `Themes` suites listing all 44 packages, plus every package's `src` in
`<source>`. They are gone (§3.8): the host cannot resolve a package's `Tests\` namespace anyway —
root `autoload-dev` maps only `Tests\` — so they failed with `Class "…\Tests\TestCase" not found`
the moment anything reinstalled.

Note that most of `tests/Unit` is integration-shaped: it boots the app, reads host config and
asserts across several packages. Only tests needing nothing from the host belong in a package.

### Static analysis

**Pint and PHPStan are shipped by `liberusoftware/package-testbench`, not by any package.** It
carries both tools as `require` and both configs — `pint.json` and `phpstan.neon` — and every
package already dev-requires it, so no package holds a `pint.json` or `phpstan.neon` of its own. The
reusable `package-tests.yml` invokes them with `--config vendor/liberusoftware/package-testbench/…`.

`PINT.md` asks for `pint.json` at each repository root and this deliberately deviates: the same
document treats inconsistent package configurations as a defect, and 44 committed copies is how they
become inconsistent. Pint has **no include mechanism**, so a shared ruleset is only reachable through
`--config`; PHPStan's `includes` would have allowed either.

**The PHPStan level is a per-package ratchet**, exactly like `coverage-threshold`: a `phpstan-level`
input in each repository's `tests.yml`, set from a measured baseline and raised only. Unset (`-1`,
not `0` — `0` is a real level) means the package is skipped with a notice rather than failed.

```bash
scripts/measure-phpstan                          # bisect each package's ceiling → storage/app/phpstan.tsv
scripts/set-phpstan-levels --workspace ~/code    # write it into each repo's tests.yml, raising only
```

The host analyses **`app/` only**, at the level `app/` passes. `modules/` and `themes/` are Composer
output, so analysing them here would use the host's resolution rather than the package's own — and a
package is only honest when analysed against the tree a consumer installs.

`phpstan.neon` carries an explicit `--memory-limit` in CI: PHPStan exhausts a 128M `php.ini` default
and reports it as `Child process error (exit code 255) while running parallel worker`, which reads
like a tool bug rather than a limit.

**Where the fleet stands against the code-level standards is `docs/CODE-CONFORMANCE.md`** — 37
findings over 122 audited rule clusters, ranked by consequence. It fixes nothing; each finding is a
separate decision.

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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- filament/filament (FILAMENT) - v5
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/horizon (HORIZON) - v5
- laravel/octane (OCTANE) - v2
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- laravel/socialite (SOCIALITE) - v5
- laravel/telescope (TELESCOPE) - v5
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v5
- phpunit/phpunit (PHPUNIT) - v13
- rector/rector (RECTOR) - v2
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== octane/core rules ===

# Laravel Octane

This application uses Laravel Octane, a long-running PHP server. The application bootstraps once and handles many requests within the same process.

- Never store request-specific state in singletons or static properties, because it can leak across requests.
- Use `config('octane.server')` to detect the active driver (`swoole`, `roadrunner`, or `frankenphp`).
- Prefer scoped bindings (`$this->app->scoped()`) over singletons for per-request services.

When working on Octane-specific features (concurrency, shared tables, memory, driver configuration, testing), invoke `octane-development` for detailed rules.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>
