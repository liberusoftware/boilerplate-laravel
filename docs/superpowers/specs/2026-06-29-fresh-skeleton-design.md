# Fresh Skeleton Rebuild — Design

Date: 2026-06-29
Branch: `chore/fresh-skeleton` (off `main`)
Repo: `src/` (remote `liberusoftware/boilerplate-laravel`)

## Problem

The current app accreted into a hard-to-maintain state: a bleeding-edge dependency
set (`minimum-stability: dev`) layered with extensive hand-written subsystems, where
the dependency wiring — not the feature code — is the main source of breakage. We want
a clean rebuild: a brand-new Laravel skeleton with every package installed fresh via
its official installer, then the hand-written subsystems **ported forward** from `main`
(not rewritten) onto that clean base.

## Decisions (locked)

- **Brand-new skeleton.** `laravel new` into `src/`, discard the current app tree.
- **Git.** New branch `chore/fresh-skeleton` off `main`. Old code stays recoverable on
  `main` and the existing feature branches. The wipe + rebuild land as commits on this
  branch. Uncommitted edits on `feat/edit-profile-clear-signal` were discarded by the
  user's explicit choice.
- **Stability: bleeding edge**, matching the current `composer.json`: PHP `^8.5`,
  Laravel `^13`, Filament `~5.1`, Livewire `^4`, Pest `5.x-dev`, `minimum-stability: dev`,
  `prefer-stable: true`.
- **Re-create the custom features too** — by porting their source from `main`, fixing
  for the fresh package versions. The deps are replaced; the feature code is reused.
- **Everything runs in Docker.** The docker stack lives in the untracked wrapper
  (`boilerplate-laravel/docker-compose.yml`) and is **not** touched. All commands run via
  `docker compose run --rm composer …` and `docker compose exec php-fpm php artisan …`.
  No host `php`/`composer`/`npm`.

## Package set

Core (same as current `composer.json`):

- Framework/auth: `laravel/framework ^13`, `bursteri/socialstream ^7` (pulls
  `laravel/jetstream ^5.1` + Fortify + teams), `laravel/sanctum ^4`, `laravel/tinker ^3`,
  `livewire/livewire ^4`, `spatie/laravel-passkeys ^1.6`
- Filament: `filament/filament ~5.1`, `bezhansalleh/filament-shield ~4`,
  `filament/spatie-laravel-settings-plugin ^5`, `biostate/filament-menu-builder ^5`
- Permissions/menus: `spatie/laravel-permission ^7`, `spatie/laravel-menu ^4.2`
- Modules: ~~`internachi/modular ^3`, `wikimedia/composer-merge-plugin ^2.1`~~ — **removed**.
  Decision: consolidate on a single custom `App\Modules\` system (see Phase 4); no
  distributable-package layer.
- Real-time/perf: `laravel/horizon ^5`, `laravel/reverb ^1`, `laravel/octane ^2.3`,
  `spiral/roadrunner-cli ^2.6`, `spiral/roadrunner-http ^3.3`
- `guzzlehttp/guzzle ^7.8`

Extras (new):

- Spatie pack: `spatie/laravel-medialibrary`, `spatie/laravel-backup`,
  `spatie/laravel-activitylog`, `spatie/laravel-query-builder`
- Observability: `laravel/telescope`, `laravel/pulse`

Dev: `pestphp/pest 5.x-dev`, `pestphp/pest-plugin-laravel 5.x-dev`, `larastan/larastan ^3`,
`laravel/pint ^1.14`, `fruitcake/laravel-debugbar ^4.3`, `spatie/laravel-ignition ^2.4`,
`nunomaduro/collision ^8.8`, `mockery/mockery ^1.6`, `fakerphp/faker ^1.23`,
`phpunit/phpunit ^13.1.8`, `laravel/sail ^1.28`, `filament/upgrade ~5.1`.

## Architecture (target — same shape as current, on a clean base)

- **Dual Filament panels** (`app/Providers/Filament/`): `AdminPanelProvider` (`/admin`,
  Team-tenant-scoped, Shield + menu-builder), `AppPanelProvider` (user-facing). Both
  disable default Fortify/Jetstream route registration; routes come from `routes/web.php`
  + `routes/socialstream.php`.
- **Layered auth**: Fortify → Jetstream (teams, profile) → Socialstream (OAuth) → Spatie
  Permission (team-scoped roles). `TeamsPermission` middleware syncs active team to
  Shield's tenant; `AssignDefaultTeam` lands users on a team.
- **Single module system**: custom `App\Modules\` lifecycle only
  (install/enable/disable/uninstall, `modules` table, `ModuleManager`, `BlogModule`
  reference). Lives under the existing `App\` PSR-4 autoload — no per-module
  `composer.json`, no merge-plugin. `internachi/modular` is **not** used.
- **Real-time**: Reverb + Echo (Pusher protocol), channels in `routes/channels.php`,
  Horizon for Redis queues, Octane+RoadRunner for HTTP.
- **Subsystems to port**: ThemeManager + theme dirs, multi-language (`SetLocale`,
  `TranslationService`, `LanguageSwitcher`), SearchService + API controllers + indexes,
  SiteSettings (spatie/laravel-settings), messaging/chat/notifications.

## Phasing — one phase = one PR, each green in CI before the next

This design is the spec for **Phase 0 only**. Each later phase gets its own
spec → plan → PR, branched off the merged Phase 0 base, in dependency order.

- **Phase 0 — Foundation (this spec).** Fresh skeleton + full package set installed via
  installers + dual Filament panels + layered auth wired + Shield generated + `.env`
  wired for docker hostnames. Deliverable: app boots, login works, `pint --test` +
  `phpstan analyse` (level 5) + `pest` all green on the bare base. **No** ported custom
  subsystems yet.
- **Phase 1 — Site settings + permissions/Shield tenancy.** Port `SiteSettings`,
  `ManageSiteSettings`, team-scoped role wiring, `TeamsPermission`/`AssignDefaultTeam`.
- **Phase 2 — Theme system.** Port `ThemeManager`, `ThemeServiceProvider`, `themes/`,
  `users.theme_preference`, Vite per-theme wiring.
- **Phase 3 — Multi-language.** Port `SetLocale`, `TranslationService`,
  `LanguageSwitcher`, `lang/`, `users.locale`.
- **Phase 4 — Module system (`app/Modules/` only).** Port the custom `App\Modules`
  lifecycle + `ModuleManager` + `modules` table + `BlogModule` + `ExternalModuleLoader`.
  `internachi/modular` + `composer-merge-plugin` are removed from the dependency set;
  no `app-modules/` layer.
- **Phase 5 — Search.** Port `SearchService`, Api controllers, search-index migration.
- **Phase 6 — Messaging/chat/notifications.** Port models/Livewire/events + Reverb
  channels + tests.
- **Phase 7 — Extras integration.** Wire media-library to avatars/profile photos,
  activitylog to key models, backup schedule, Telescope + Pulse dashboards (gated to
  admins).

## Phase 0 build order (the implementable steps)

1. From branch `chore/fresh-skeleton`: commit this spec, then `git rm -r` the old app
   tree (keep `docs/superpowers/`, `.git/`). Scaffold `laravel new` content into `src/`.
2. `composer require` core packages; set `minimum-stability: dev`, `prefer-stable: true`,
   platform php 8.5, merge-plugin config, allow-plugins.
3. `php artisan socialstream:install` (Livewire stack, teams, chosen OAuth providers).
4. Install Filament + create second panel; `AdminPanelProvider` Team tenancy.
5. `spatie/laravel-permission` install + `filament-shield` install + `shield:generate`.
6. (removed) — no `internachi/modular` / merge-plugin; module system is Phase 4,
   `app/Modules/` only.
7. Reverb + Horizon + Octane/RoadRunner publish; Sanctum.
8. Filament plugins: spatie-settings, menu-builder; `spatie/laravel-menu`, passkeys.
9. Extras: medialibrary, backup, activitylog, query-builder; Telescope + Pulse (publish +
   migrations).
10. Dev tools: Pest + pest-plugin-laravel, Larastan, Pint, debugbar, ignition, collision,
    sail, filament/upgrade.
11. `.env` / `.env.example`: DB host `boilerplate-laravel-mysql`, Redis
    `boilerplate-laravel-redis`, Mailpit `boilerplate-laravel-mailpit`, Reverb config,
    `APP_URL=http://boilerplate-laravel.test`, SQLite for tests in `phpunit.xml`.
12. `composer install` in container, `migrate:fresh --seed`, `npm install && npm run build`.

## Error handling / risk

- **Dependency resolution on dev stability is the top risk.** If `composer require` for the
  full set fails to resolve, install in the build-order groups above (auth → filament →
  permission/shield → modules → realtime → extras → dev) so a conflict is isolated to one
  group, not the whole graph. Document any package pinned/held back in CLAUDE.md's "Known
  Upgrade Blockers".
- **Socialstream is a fork** (`bursteri/socialstream`) — its installer drives Jetstream;
  run it before hand-editing panels so its stubs land first.
- **`shield:generate` needs panels registered** — run after both panel providers exist.
- All destructive/irreversible steps already confirmed by the user (discard dirty tree,
  wipe app tree).

## Verification (Phase 0 done = all true)

- `docker compose up -d --build` boots; `http://boilerplate-laravel.test` serves.
- Register + login works (Socialstream/Jetstream flow); `/admin` loads for an admin user.
- `docker compose exec php-fpm vendor/bin/pint --test` clean.
- `docker compose exec php-fpm vendor/bin/phpstan analyse` clean at level 5.
- `docker compose exec php-fpm vendor/bin/pest` green.
- Spec + rebuild committed on `chore/fresh-skeleton`; PR labelled `enhancement`,
  reviewer `curtisdelicata`.

## Out of scope for Phase 0

Custom subsystems (themes, custom modules, search, translation, messaging) — those are
Phases 1–6. Extras are installed in Phase 0 but only *integrated* into models/UI in
Phase 7.
