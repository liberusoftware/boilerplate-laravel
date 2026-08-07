# Conformance spec and migration plan

How `boilerplate-laravel` becomes conformant with
[liberusoftware/documentation](https://github.com/liberusoftware/documentation), whose
`architecture/MODULES.md` is declared source of truth.

Produced by the wayfinder map
[Wayfinder: conform boilerplate-laravel to the Liberu documentation standards](https://github.com/liberusoftware/boilerplate-laravel/issues/612).
Every decision below links to the ticket holding its reasoning. **This document plans; it changes nothing.**

Section references are to `architecture/MODULES.md` unless stated.

## 1. Scope

**In scope — structural conformance plus testing and CI gates:** `architecture/MODULES.md`,
`projects/boilerplate/BOILERPLATE.md`, `architecture/REPOSITORIES.md`, `standards/TESTING.md` §13,
`standards/CI.md`.

**Deferred to a later map — code-level standards:** `PHP.md`, `PSR.md`, `PINT.md`, `FILAMENT.md`,
`LIVEWIRE.md`, `THEMES.md` and their delivery checklists. Renaming and splitting packages first
would invalidate a code-level audit done now.

**Out of scope entirely:**

- Normalising the wider ecosystem's vendor prefixes (`liberu-cms/*`, `liberu-genealogy/*`) — other repositories, other owners.
- Reconciling the documentation repo with the `liberusoftware/` vendor (see §3.2) — a docs PR with different reviewers.

## 2. The fleet today

44 `liberu-module` packages under `modules/`, 4 `liberu-theme` packages under `themes/`, all edited
in this monorepo and rsynced out by `scripts/publish-components`. Two contract packages
(`analytics-contracts`, `localization-contracts`) already live repo-first as `type: library` in
`vendor/`, with no manifest — which §10 permits.

After the exiles and splits, the fleet lands at **40 module packages + 4 themes**:
`foundation` 26, `presentation` 9, `adapter` 4, `capability` 1, `product` 0.

## 3. Decisions

### 3.1 Source of truth flips to module-repo-first

**Standard (§6, §6.2):** the independent module repository is source of truth; you never edit
installed module files in a consuming application; changes flow module repo → tag → `composer update`.
`/modules` stays tracked, and CI fails on an uncommitted `modules/` diff.

**Today:** the inverse. `CLAUDE.md` documents editing here and rsyncing out.

**Changes to:** `modules/` becomes Composer output. `publish-components` becomes a one-time splitter, then **removed** — done in step 9.

**Enforced by:** the §6.2 clean-install zero-diff check in the host's `install.yml`.

**Authoring afterwards** — [decided in #633](https://github.com/liberusoftware/boilerplate-laravel/issues/633).
A package is edited in a clone at `~/code/<repo>`, cloned on demand rather than kept as a permanent
44-repo workspace; `modules/` is read-only Composer output. `publish-components` is replaced by
`scripts/fleet`, which edits, tests, commits and pushes across N repositories but **never tags** — a
push is recoverable, a tag is what Packagist publishes and what `ModuleValidator` pins the host to.
`modules/` stays tracked, so a release wave ends in one host commit carrying the `modules/` diff and
`composer.lock`.

> **Verified failing today.** A root `composer install` deleted **110 tracked files** and modified
> **92**. The zero-diff gate cannot be switched on until every package has been republished from
> final source — step 8.
>
> **Correction, measured across all 48 packages.** The original reading of that clobber — that the
> published packages are stale and carry no `tests/`, `phpunit.xml` or `.github/` — is **wrong**.
> They carry all three. The monorepo is the side that is behind:
>
> | | |
> | --- | --- |
> | Packages diverged from their published repo | **48 of 48** — none identical |
> | Version skew | 47 at local `1.0.3` vs published `1.0.4`; `module-manager` at `1.0.3` vs `1.0.6` |
> | Present upstream, absent here | `tests/Pest.php` in all 48, `tests/TestCase.php` in 47 — the per-package Testbench bootstrap that `package-testbench` replaces |
> | Differing in both | 8 files in every package: `composer.json`, `module.json`, `README.md`, `CHANGELOG.md`, `phpunit.xml`, `.github/workflows/tests.yml`, and both shipped test files |
>
> The divergence runs **both ways** — the monorepo's `tests.yml` is richer, the four themes'
> `ThemeMetadataTest.php` exists only here — so reconciliation is a merge, not a mirror. Mirroring
> `module-blog-core` from here would regress it `1.0.4` → `1.0.3`, drop its `features` key and flip
> `default_enabled` from `true` to `false`.
>
> **This blocks steps 0, 7 and 8**, which all publish from the monorepo. `publish-components` rsyncs
> with `--delete`, so running it in its current form destroys the upstream deltas across every
> package it touches. Which side wins, per package, is an open decision.
>
> Incidental: `activity-comments`, `clear-signal`, `module-manager` and `theme-support` shipped a
> **committed `vendor/` directory** upstream — around 10,300 files each, so a five-file change
> published as a several-hundred-file diff. **All four have since untracked it** and gitignore
> `vendor/` and `composer.lock`; a library must not ship a dependency tree, since a consumer
> resolves its own.
>
> Re-measured at the time with `scripts/audit-divergence`, into `storage/app/divergence.tsv`. That script
> was removed in step 9: once `modules/` is Composer output there are no longer two copies to compare,
> and §6.2 answers the same question continuously in CI.
>
> **Reconciled.** Resolved in favour of upstream, pulled down with `composer update
> "liberusoftware/*"` rather than an rsync — Composer is the mechanism §3.1 is moving toward, and
> `ModuleValidator` compares each manifest against `Composer\InstalledVersions`, so the lock has to
> move with the files. Version skew is now **zero across all 48**.
>
> Three host changes were required by upstream's contracts, none cosmetic:
>
> - `PrivilegedActor` gained `authorizationIdentifier()` and `authorizationType()`, and promoted `hasRoleInAnyTeam()` to public. The pivot query moved into `Liberu\Foundation\Authorization\Services\AnyTeamRoleLookup`, so `App\Models\User` now delegates instead of keeping its own copy.
> - `Manifest::fromFile()` now requires a `features` key.
> - Package `ServiceProviderTest`s assert `getProvider()` after Testbench boots rather than calling `register()`, so the architecture rule matches either shape.
>
> **The monorepo still wins on 11 files, deliberately**, and each is a defect upstream should adopt:
>
> | Kept here | Why |
> | --- | --- |
> | `.github/workflows/tests.yml` × 48 | upstream's references `shivammathur/setup-php@v3` and `ramsey/composer-install@v4`, **neither of which exists** — upstream CI fails on every package, every run |
> | `theme-support/src/Support/theme_helpers.php` | upstream 1.0.4 ships this file containing **only `<?php`** — all seven helpers deleted while `autoload.files` still loads it. A hard regression its broken CI never caught |
> | `analytics-core` `DestinationsCoverageTest`, `module-manager` `ManifestTest` | upstream still carries both §4 defects |
> | 8 tests absent upstream | `ThemeMetadataTest` × 4, `CurrencyContextTest`, `TranslationServiceHttpTest`, `GoogleDestinationTest`, `MetaDestinationTest` |
>
> Because `modules/` is now Composer output, those 11 survive only until the next `composer update`.
> **They must be pushed upstream and released before the §6.2 zero-diff gate can ever go green** —
> that is now the real content of step 8, and it is larger than "republish from final source".
>
> **Pushed upstream.** All 48 package repositories now carry the workflow, the restored helpers and
> the missing tests, each verified locally against its current release before pushing. **CI is green
> on 48 of 48** — previously every repository failed every run it had ever attempted.
>
> Turning CI on immediately found two defects nobody had catalogued, both invisible while the
> workflow could not start:
>
> - **`module-manager` did not work standalone at all** — 33 errors. `tests/TestCase.php` registered `Livewire\LivewireServiceProvider` without `livewire/livewire` in `require-dev`, and repointed the application base path at the package root, which has no `bootstrap/cache`. Confirmed against a pristine clone, so it predates this effort. Now 33 passing.
> - **`theme-support`'s own 17 tests exercise the helpers its release had emptied**, which settles that the gutted file was a regression rather than an intentional removal.
>
> **Released.** All 44 packages are tagged `1.1.0` — 25 in the step 2 wave and the remaining 19 in
> step 3, once a clean install proved they were still resolving `1.0.4`. `composer update` now
> resolves the fixed tree, and the §6.2 zero-diff gate is green.

### 3.2 Composer vendor stays `liberusoftware/`

**Standard (§9):** the naming table specifies `liberu/{...}`, and names the mandated packages
`liberu/package-testbench` and `liberu/composer-installer`.

**Decision:** **packages remain `liberusoftware/*`.** No vendor rename.

**Consequence:** the documentation repo and this repo now disagree *by intent*. Every package name
the standards mandate carries the wrong vendor for this repo. **This requires a PR to
`liberusoftware/documentation`** — recorded as out of scope here, but it is a real obligation, not
an oversight.

What this simplifies: nothing is republished under a new name, nothing is abandoned (and abandoned
packages can **block** installs, not merely warn), no Packagist vendor permission is needed.

Package-level renames in §3.3 still apply — within the existing vendor.

### 3.3 Package naming

[Does every foundation package take a -core suffix?](https://github.com/liberusoftware/boilerplate-laravel/issues/614) ·
[Where does the foundation-filament umbrella split?](https://github.com/liberusoftware/boilerplate-laravel/issues/616)

**`-core` marks provider-neutrality, and only where a sibling provider adapter ships today.**
Not "core of the application". An adapter must exist, not merely a provider-shaped contract — §4
rule 4 forbids speculative abstraction.

| Now | Becomes | Why |
| --- | --- | --- |
| `identity` | `identity-core` | has `identity-socialstream` |
| `localization` | `localization-core` | has `localization-mymemory` |
| `analytics-core` | unchanged | has two adapters |
| `application-core` | **`application`** | no adapters — suffix stripped, no exception |

`search`, `currency-context` and `files-media` stay bare despite provider-shaped contracts; they
rename when an adapter ships.

**Presentation adapters take `module-{module-name}-{surface}`** (§9), inheriting the renamed module:

| Now | Becomes |
| --- | --- |
| `identity-filament` | `module-identity-core-filament` |
| `localization-livewire` | `module-localization-core-livewire` |
| `search-api` | `module-search-api` |
| `settings-filament` | `module-settings-filament` |
| `organizations-teams-filament` | `module-organizations-teams-filament` |
| `roles-permissions-filament` | `module-roles-permissions-filament` |

Provider adapters are **unaffected** — §9 builds them from the capability, so `identity-socialstream`,
`localization-mymemory`, `analytics-google`, `analytics-meta` keep their names.

**`foundation-filament` is dissolved** — it is an umbrella presenting three independent modules,
which §5.6 forbids:

| Piece | Becomes |
| --- | --- |
| `FoundationAccountPlugin` + `AccountSecurity` | `module-sessions-devices-filament` (app panel) |
| `FoundationAdminPlugin` + `FoundationOperations` | `module-module-manager-filament` (admin panel; requires observability for its gate) |
| `Support/ThemeColors` | **moves to the host `app/`** — §6 assigns panel composition to the root application |

`module-module-manager-filament` stutters. Accepted, not excepted: the rule stays mechanically
checkable, the same reasoning that stripped `application-core`'s suffix.

`theme-support`'s Livewire splits into `module-theme-support-livewire`, leaving it unambiguously foundation.

### 3.4 Scope: what may live here

[Where do the out-of-scope blog and messaging packages land?](https://github.com/liberusoftware/boilerplate-laravel/issues/613) ·
[Is each manifest's section 5 category correct?](https://github.com/liberusoftware/boilerplate-laravel/issues/617)

**`BOILERPLATE.md` is authoritative.** Six packages leave, each to its own repository — none deleted, none merged:

| Package | Becomes | Why |
| --- | --- | --- |
| `blog-core`, `blog-filament` | `blog-core` + `module-blog-core-filament` | no home in the docs; `cms-laravel` already ships `cms-posts` |
| `messaging`, `messaging-api`, `messaging-filament` | `social-network-messaging` + `-api`/`-filament` | the docs place messaging under social-network explicitly |
| `search-demo` | `search-demo` | not a documented capability; also **default-enabled today**, against §12, and its migrations create unprefixed `posts` and `groups` tables |

**Manifest `category` has exactly five legal values:** `foundation`, `capability`, `adapter`,
`product`, `presentation`. `contract` and `aggregate` are excluded — such packages carry no
`module.json` at all. Corrections: `analytics-core` → `capability`, `theme-support` → `foundation`.

### 3.5 Namespaces

[Does the Liberu\Foundation namespace root survive?](https://github.com/liberusoftware/boilerplate-laravel/issues/625)

**`Liberu\Foundation\*` and `Liberu\Themes\*` survive.** §9's `Liberu\{Domain}\{Capability}` admits
two readings; `{Domain}` is the **package family**, so foundation packages are already correct and
312 files stay put.

**The namespace names the capability; the package name adds qualifiers.** That dissolves six of
seven apparent mismatches — `organizations-teams` → `Organizations` is the doc slug adding `-teams`,
not a conflict. It also keeps `analytics-core` free of an `AnalyticsCore` namespace, since `-core`
is a packaging fact.

Two real changes:

- `Liberu\Foundation\Authorization` → **`Liberu\Foundation\RolesPermissions`** (~23 files) — the only genuinely different word; `BOILERPLATE.md`'s `roles-and-permissions` is authoritative.
- The **analytics family** moves to `Liberu\Analytics\{Core,Google,Meta}` (~21 files), following its recategorisation to `capability`. Its contracts are already at `Liberu\Analytics\Contracts`. This is §9's `liberu/payment-core` → `Liberu\Payment\Core` example exactly.

### 3.6 Discovery and enablement

**Standard (§12):** discovery derives from validated installed manifests, not literal lists;
defaults come from each manifest's `default_enabled`.

**Today:** a literal 41-name `$applicationModules` list plus 3 `$optionalAdapters`, all manifests
hardcoded `default_enabled: false`, with an architecture rule enforcing the inverse.

**Changes to:** manifests declare their own `default_enabled`; `config/modules.php` keeps only the
`MODULES_ENABLED`/`MODULES_DISABLED` env overrides; the literal lists are deleted.

**Enforced by:** a new architecture rule (§3.8).

### 3.7 Test bootstrap

[What does liberu/package-testbench own?](https://github.com/liberusoftware/boilerplate-laravel/issues/615) ·
[Do contract packages get tests and CI?](https://github.com/liberusoftware/boilerplate-laravel/issues/626)

**`liberusoftware/package-testbench` is authored** and owns exactly four things:

1. `PackageTestCase` — Testbench base, registering the provider the manifest declares. Manifest-driven, so `getPackageProviders()` overrides disappear fleet-wide.
2. `PackageRoot` — locates the package and reads its manifest. *This is* the package-loading declaration.
3. **Three shipped boundary suites** — `Module`, `Theme`, `Contract`. Packages ship **zero** boundary test files; `phpunit.xml` points into `vendor/`. A new rule becomes a testbench release, not a 43-repo sweep.
4. **v1.1 — a shared test actor** implementing `PrivilegedActor`, `ObservabilityActor`, `OrganizationActor`, `ConnectedAccountOwner`, plus its migration. This is what unblocks the 16 host tests that need `App\Models\User`.

Three suites rather than one because the manifests genuinely differ: the canonical `theme.json` has
**no `requires` key**, so a single suite would let a theme run half its assertions and still report
green. The `Contract` suite must **not** use `PackageTestCase` — contract packages have no provider
and are deliberately framework-free.

`liberusoftware/composer-installer` consumes the `Contract` suite **plus real unit tests** for path
computation, invalid-name rejection, traversal rejection and the hardened collision check. Those
behaviours break all 40 packages at once and no boundary assertion covers them.

### 3.8 Architecture rules: 12 → 6

[Rewrite the architecture rule set](https://github.com/liberusoftware/boilerplate-laravel/issues/623)

**A rule lives where it can be acted on.** A host failure the host cannot fix is a bad rule.

- **7 move** to the testbench boundary suites: package metadata, provider registration, no `App\` dependency, no Filament in non-presentation modules, `-api` adapters not importing domain models, `-filament` modules declaring plugins, themes shipping declared assets.
- **3 survive** in the host — whole-graph only: theme parent resolution, Composer owning every autoload boundary, cross-package namespace dependencies declared.
- **1 dies**: "runs and measures every installed module", together with the host's `Modules`/`Themes` testsuites and package source in its coverage `<source>`.
- **1 is replaced**: runtime-selection accounting asserted against the deleted literal lists.
- **3 are new**: no duplicate Filament plugin ids; enablement derives from manifests; every required Liberu package uses the `liberusoftware/` vendor.

**Two of the twelve do not currently work** and must be fixed, not merely relocated:

- *"accounts for every installed module"* — `BindingResolutionException: Target class [config] does not exist`. The Architecture suite does not boot the app.
- *"resolves every declared theme parent"* — misuses `toHaveKey`'s second argument (the expected **value**) as a failure message. It fails the moment any theme declares a parent, and has never enforced anything.

Both rules that filter on the vendor prefix must **derive** it from the package's own name. The
prototype showed a hardcoded prefix failing 43 packages under one vendor and passing 44 under the other.

### 3.9 CI

[Which CI workflow set does a package repo need?](https://github.com/liberusoftware/boilerplate-laravel/issues/621)

**Package repos get the §7 three** — `tests.yml`, `install.yml`, `compatibility.yml` — as thin
callers of **reusable workflows in `liberusoftware/.github`**. (Three separate workflow files with
different triggers, not one workflow with three jobs — see the step-5 record below for why that
distinction stalled the organisation's Actions queue.) Rule 20 (each repo owns its workflows)
and CI.md (a reusable workflow owns repeated setup) reconcile: a callable Actions workflow is not a
Composer package.

**Host:** delete redundant `main.yml`, fold `security.yml` into `tests.yml`, add `release.yml`, and
put the §6.2 zero-diff check in `install.yml`. `deploy-staging.yml` is not added — CI.md marks it optional.

### 3.10 Coverage

[Measure per-package coverage](https://github.com/liberusoftware/boilerplate-laravel/issues/618)

Pre-migration baseline: fleet **15.1%** (193/1281 lines), median package **12.82%**, **4 Filament
packages at 0%**, 26 of 40 under 15%.

**Step-6 measurement**, all 44 packages resolved from nothing and run standalone:

| | pre-migration | step 6 |
| --- | --- | --- |
| Median package | 12.82% | **20.0%** |
| Under 15% | 26 of 40 | **20 of 44** |
| At 0% | 4 | **4** — the same four |
| At 100% | — | **6** |

Thresholds are set on **all 44**. The four that measured 0% were skipped from the first wave — a
zero threshold is indistinguishable from having no gate — and earned a first real test instead:

| | was | now |
| --- | --- | --- |
| `roles-permissions-filament` | 0% | **100%** |
| `settings-filament` | 0% | **98.7%** |
| `organizations-teams-filament` | 0% | **67.8%** |
| `identity-core-filament` | 0% | **59.7%** |

Each composes the smallest panel that registers the plugin its manifest declares — a Filament
resource is only reachable through a panel, and these packages ship plugins rather than panels. The
fixture panel is deliberately not a copy of the host's, which is tenant-scoped, Shield-gated and
themed from site settings; reproducing it would assert on the host's composition instead.

Per `TESTING.md` §13 — "a threshold below 100% is a migration state, not a policy" — each repository
sets its initial CI threshold from its **step-6** measured figure, not the pre-migration figure
above. Do not set a 0% floor for the four Filament packages; give them a first real test instead.

**The threshold lives in the package's `tests.yml`.** Not in `phpunit.xml` — PHPUnit's `<coverage>`
element carries report configuration and no minimum, so a threshold written there is silently
ignored. And not in a Composer script either: the reusable workflow in `liberusoftware/.github`
already takes a `coverage-threshold` input, defaulting to `0`, and switches between `pest` and
`pest --coverage --min=N` on it. Two files carrying the same number is two files that can disagree.

```yaml
jobs:
  tests:
    uses: liberusoftware/.github/.github/workflows/package-tests.yml@main
    with:
      coverage-threshold: 61
```

**No `<source>` narrowing is needed.** All 44 packages already scope `<source>` to `src`, and no
configuration or migration PHP lives inside it — `config/` and `database/` are siblings of `src/`,
outside the measured tree. The measured scope is already *meaningful owned PHP* as `TESTING.md` §13
defines it, and the low figures are honest: `feature-flags` reports 7.1% because its provider is
covered and `Support/FlagEvaluator` has no tests at all.

Two scripts do the work, and they are separate because the second must be re-runnable as coverage
grows:

- `scripts/measure-coverage` resolves each package from nothing and runs its suite under pcov exactly
  as its CI does, writing `storage/app/coverage.tsv`.
- `scripts/set-coverage-thresholds` writes each figure into that package's `tests.yml`. It **ratchets
  only upward** — a package that has lost coverage keeps its old threshold and is reported, because
  that is a regression to look at rather than a floor to lower.

`scripts/migrate-testbench` rewrites `tests.yml` from a template, so it now preserves an existing
`coverage-threshold` block. Without that, one re-run silently resets every ratchet in the fleet.

## 4. Defects that must be fixed en route

Found by executing rather than reading. None is optional.

| Defect | Evidence | Fix |
| --- | --- | --- |
| **No package installs standalone** | **0 of 48** declare `config.allow-plugins`; `composer update` aborts on `pestphp/pest-plugin`. `CLAUDE.md:58` documents a workflow that cannot work. | add `config.allow-plugins` to every package |
| **Installer lacks `plugin-modifies-install-path`** | Composer 2.2.9+ requires it for path-modifying plugins; absence risks Composer assuming the wrong location — which would surface as phantom `modules/` diff churn | declare it |
| **Installer collision detection is per-run only** | in-memory array on one instance; misses a directory left by a removed package | check the filesystem |
| **Host cannot run package tests** | `orchestra/testbench` absent at host; `Modules` suite dies with `Class "Orchestra\Testbench\TestCase" not found` | resolved by dropping the suite (§3.8) |
| **`analytics-core` reaches into a sibling** | its test needs `Liberu\...\Analytics\Google\Support\GoogleDestination` — breaks §4 rule 10 | test against the contract |
| **`module-manager` tests read the host** | `ManifestTest` globs `$root.'/modules/*/module.json'` — passes only inside the monorepo | make it a composition test or fixture-driven |
| **Two architecture rules broken** | see §3.8 | fix while rewriting |
| **`composer.lock` out of date** | `composer install` warns it disagrees with `composer.json` | resolve before step −1 |
| **`publish-components` would publish `vendor/`** | its rsync excludes only `.git`, but `modules/*/vendor` and `modules/*/composer.lock` are gitignored here and appear the moment a package's standalone suite is run — the very workflow `CLAUDE.md:58` prescribes. A dry rsync of `modules/analytics-core` alone listed **23,990** `vendor/` entries for transfer | fixed here: `vendor/`, `composer.lock`, `node_modules/` and `.phpunit.result.cache` are excluded |
| **`publish-components` can fast-forward `main` from a feature branch** | the meta step is `git push … HEAD:$branch`, so publishing while checked out anywhere but `main` silently advances `main` to the working branch | fixed here: the meta push refuses unless `HEAD` is on `$branch` |
| **34 packages published with no `tests/` directory** | the migration left `tests/` empty for every package with no tests of its own, and Git does not track an empty directory — so the 1.2.0 tarballs had none, and Pest aborts before any suite runs with `The test directory [tests] does not exist`. The fleet sweep did not catch it: the empty directory was still on disk locally. Found only when a `composer update` reinstalled a package over it | a tracked `tests/.gitkeep`, written by `scripts/migrate-testbench`; republished as 1.2.1 |
| **Two modules cannot boot standalone at all** | Testbench runs no package discovery, so `localization-core-livewire` and `theme-support-livewire` failed every boundary test with `Target class [livewire.finder] does not exist`. Their deleted per-package `TestCase` had hardcoded `LivewireServiceProvider`, which is why nobody had noticed the packages themselves declare no way to boot | fixed in testbench 1.5.0: `PackageTestCase` registers `extra.laravel.providers` of every direct dependency — Laravel's own discovery, scoped to what the package requires |
| **`localization-mymemory` boots against an implementation it does not depend on** | its provider calls `$this->app->make(TranslationProviderRegistry::class)`, but it requires only `localization-contracts`; nothing in its dependency graph binds one. Standalone it throws `Target [TranslationProviderRegistry] is not instantiable`. The deleted `TestCase` hid this behind an anonymous fake | `require-dev` on `liberusoftware/localization-core`, which testbench 1.5.0 boots. Runtime independence from the implementation is preserved; only the test composition gains one |
| **`search` is built around the demo it is losing** | `SearchService::searchPosts()`/`searchGroups()` read `config('search.models.post')`/`.group`, which ship as `null` and were set only by `search-demo`'s provider. With the demo exiled, `/api/search/posts` and `/api/search/groups` raise `Class name must be a valid object or a string` — a fatal, not an empty result | guard the unconfigured type now; move the demo-shaped methods and their two controller actions into `search-demo` at step 2 |

**Fixed in this repository** — the four that live here, verified rather than asserted:

- **`config.allow-plugins`** now declared by all 48 packages, pinned by a new architecture rule. `composer update` in `modules/search`, `modules/analytics-{core,google,meta}`, `modules/module-manager` and `themes/dark` succeeds **without `--no-plugins`**, and each package's suite then runs green standalone.
- **`analytics-core`** no longer imports `Analytics\Google\*` / `Analytics\Meta\*`; those assertions moved into the adapters' own suites, so no coverage was lost.
- **`module-manager`'s `ManifestTest`** reads its own `module.json` instead of globbing the consuming application. The fleet-wide parse moved into the host's existing package-metadata rule rather than becoming a new one.
- **Both broken architecture rules** work: the `Architecture` suite now boots the app for `config('modules.*')`, and the theme-parent rule throws with the offending theme named. Each was mutation-tested — deliberately violated and confirmed red — because both were previously green by accident.

**Fixed in `liberusoftware/composer-installer`** — branch `fix/installer-defects`, unpublished. `plugin-modifies-install-path` declared; collision detection now reads the working tree, so a directory whose `composer.json` names a different package fails the install instead of being written over. A real `composer install` was used to confirm both, and all 48 packages in this tree still resolve to their current paths. One consequence for §5 step 8: a package that keeps its directory while changing its Composer name now fails until the old directory is removed — by design, but it makes a rename `rm -rf` the old target, then install.

**`composer.lock` is no longer stale.** A root `composer install` reports "Nothing to install, update or remove" with no lock warning, and `composer validate` passes. Note this does **not** exercise §6.2: Composer no-ops when the installed state already matches the lock. The zero-diff gate needs a *clean* install, which is the path that reinstalls every package over the tracked source.

**The zero-diff gate is green, at step 3 rather than step 8.** `rm -rf vendor && composer install` now rewrites **nothing** under `modules/` or `themes/`. It took two passes to get there: the first clean install still rewrote 23 files, because the 19 packages outside the step 2 wave were resolving `1.0.4` tags that predate the workflow fix and the theme metadata tests §3.1 records the monorepo winning on. Their `main` branches were already correct — only the tag was stale. Tagging them `1.1.0` closed it. The check is now enforced in `install.yml`, so it cannot regress silently, and step 8's remaining content is the split itself rather than "republish from final source".

**Blocking, found while starting step 0:** every one of the 48 packages has diverged from its
published repository, and the monorepo is the side that is behind. See the correction in §3.1. No
package may be published from here until that is reconciled, because `publish-components` mirrors
with `--delete`.

**Step 0 is done.** The six left; the host is green at **38 modules + 4 themes**, 265 passed across
`Architecture`, `Unit` and `Feature`, all 13 architecture rules passing.

Each of the six was confirmed **byte-identical** to its published repository — including `.github/` —
before its directory was removed, so nothing was published from here and nothing was lost. Removal
went through `composer update`, not `rm`: the installer owns those paths, so dropping the requires is
what deletes the directories, and the lock records it.

Two things the plan did not anticipate, both found by running the suite afterwards:

- **`search` was built around the demo** — see the new row above. `SearchService` now resolves a
  searchable type through one `modelFor()` helper that returns null for an unconfigured or
  non-existent class; `searchPosts()`/`searchGroups()` return an empty page, and `searchAll()` omits
  the type entirely rather than reporting zero results for something the composition does not have.
  `modules/search/tests/Unit/UnconfiguredModelTest.php` covers both, written red first.
- **The host's search tests were the demo's tests.** 39 of them drove `Liberu\Search\Demo\Models\*`.
  They are not demo tests, though — what they actually pin down is `SearchService`'s two security
  rules, that a draft is unreachable through search and that a non-public group is, and those live in
  the surviving package. So the host now owns two fixture models under `tests/Fixtures/`, registered
  from `Tests\TestCase::refreshApplication()` — the one hook that runs *before* `RefreshDatabase`
  migrates. Their tables are `search_fixture_posts`/`search_fixture_groups`, prefixed deliberately:
  §3.4 faulted `search-demo` for creating unprefixed `posts` and `groups`, and re-creating them under
  `tests/` would have reintroduced the same objection. The six test files changed by two `use` lines
  each; every assertion is unchanged.

The tests that were **deleted** rather than re-fixtured are the ones with no surviving subject:
`MessageTest` (5), `MessageApiTest` (15), `BlogPostAuthorTest`, `BlogResourceCoverageTest`, the blog
index case in `OperationalCoverageTest`, and the message-policy case in `ModuleSupportCoverageTest`.
They exercise exiled code, so they belong to the exiled repositories, and they cannot run there until
the testbench ships its v1.1 actor (§3.7) — every one of them needs `App\Models\User`. They are
preserved at `33550079` and must be adopted by `module-messaging`, `module-messaging-api` and
`module-blog-*` as part of step 5; copying them across now would only add files nothing runs.

Host tests that merely *used* an exiled package as an example were repointed, not deleted:
`CanonicalModuleDiscoveryTest` now orders `settings` before `settings-filament`, and
`ModuleFilamentPluginsTest` disables `identity-filament` — chosen because no other manifest requires
it, so the test exercises plugin composition rather than dependency resolution. `ResourceDefinitions`
and `SuperAdminGateTeamAgnosticTest` swapped the blog `Post` for a surviving model.

Also corrected while here: the landing page advertised "real-time chat & messaging" as a shipped
feature in three places. Two remain unaddressed and are **not** a step-0 gate — the hero mock still
renders a Messages screen, and Reverb is still unwired (`CLAUDE.md`); both are a design decision, not
a mechanical strip.

**Step 2 is done, and it ended in a release wave.** The renames are applied, published as **1.1.0**
across 25 repositories, and root is repointed. `composer update` reported 8 installs, 17 upgrades
and 6 removals — the removals being `application-core`, `foundation-filament`, `identity`,
`identity-filament`, `localization` and `localization-livewire`, the names the renames replaced.
The host is green again at 265 passed, 12 skipped, all 13 architecture rules, and the reinstall
produced a **zero diff in `modules/` and `themes/`**, which is the first time §6.2's gate has
actually been exercised: Composer fetched all 44 packages from GitHub over the tracked source and
changed nothing.

Five of the eight "new" repositories were **renames**, not creations, so history, tags and inbound
URLs survive; only `module-module-manager-filament`, `module-sessions-devices-filament` and
`module-theme-support-livewire` were created. `module-foundation-filament` is now orphaned.

Four things the plan did not anticipate:

- **The atomic-commit principle does not extend to verification** — see the correction opening §5.
  The gate for a rename wave is each package's own suite, and eight packages could not even install
  standalone until the wave landed, because their dependency had been renamed in the same wave.
- **`publish-components` would have shipped `vendor/`** and could have fast-forwarded `main` from a
  feature branch. Both are new rows above, both fixed here.
- **It also required an SSH key it never needed.** The clone and meta push hardcoded
  `git@github.com:` while every mutation in the script goes through `gh`, which authenticates over
  https; on a machine with no key this failed with `Host key verification failed` and published
  nothing. The remote now follows `gh config get git_protocol`.
- **Publishing mirrors all 40 modules, not the ones a branch changed.** Three untouched packages
  still pushed, because the monorepo was *behind* what had been published from them. §3.1 reconciled
  that divergence once, by pulling upstream down through `composer update` — but the tracked source
  drifted back, and `--delete` sent the stale copy out again. All three were broken standalone, and
  the first sweep missed them by covering only changed packages: `module-manager` registered a
  `Livewire\LivewireServiceProvider` it does not require, reproducing **the exact 33-failure defect
  §3.1 records as already fixed upstream**, and `currency-context` and `localization-mymemory` each
  bound a test case per file on top of their own `Pest.php` binding. Caught by re-sweeping **all
  40**, fixed, and given 1.1.0 tags of their own — their `1.0.x` tags point at the broken commits and
  root requires `^1.0`, so without a new tag Composer would have kept resolving the break. This also
  closes §3.1's last open item, that the fleet was "fixed on `main` but not re-released".
  **Two lessons: the publish gate is every package, not the diff; and a reconciliation that is not
  committed to the tracked source does not hold.**

Not done in step 2, and deliberately: the `Modules` and `Themes` testsuites still fail from the host
(`Class "Liberu\Foundation\ActivityComments\Tests\TestCase" not found`) because root `autoload-dev`
maps only `Tests\`. That is the §3.8 defect whose fix is dropping the suites in step 3, so the host
gate remains `Architecture`, `Unit` and `Feature`. Moving `SearchService`'s demo-shaped methods into
`search-demo` also remains open.

**Step 3 is done, and the rule count went the wrong way — 12 to 15, not 12 to 6.**

§3.8's arithmetic assumed 7 rules could move to `package-testbench`'s boundary suites. The testbench
is authored, but **no package requires it yet** — that is step 4. Deleting those 7 now would leave
the fleet unguarded across two steps to make a count in a plan come true, so they stay until step 4
actually lands their replacement. What did change is the other five:

- **2 replaced.** The runtime-selection rule diffed installed modules against the literal lists,
  and the coverage rule policed a `phpunit.xml` that no longer lists packages. In their place:
  enablement derives from the manifests and config holds no names, and both override levers behave
  — including that disabling a depended-on module is a `DependencyResolutionFailed`, not a quiet
  omission.
- **3 added**, one of them §3.8's: the fleet publishes under one Composer vendor, and every declared
  Filament plugin class exists. Both rules that filter on the vendor prefix now **derive** it via
  `packageVendor()`.

**Two rules §3.8 asked for were dropped for failing their mutation test rather than passing it.**
A static plugin-id uniqueness rule and a renamed-package rule can never fire: `ModulePlugins` and
`ModuleValidationGuard` both reject those conditions while the Architecture suite boots the
application, so the rule is at best the second thing to notice. Mutating each one proved it — the
failure came from the runtime guard, not the rule. The plugin guards moved to
`tests/Unit/ModuleFilamentPluginsTest.php`, against real manifests on disk, where deleting the guard
turns the test red. **A rule that cannot be the thing that catches the fault is not coverage.**

`config/modules.php` names nothing now. `ModuleRegistry::resolve()` already honoured each manifest's
`default_enabled`, so the two literal lists were only ever a second source of truth that could drift.

`phpunit.xml` is three suites and `app` alone. The `Modules` and `Themes` suites could not have
worked from the host in any case — root `autoload-dev` maps only `Tests\`, so they died with
`Class "…\Tests\TestCase" not found` the moment anything reinstalled. `vendor/bin/pest` runs clean
again without a suite filter: 269 passed, 12 skipped. Measuring `app` alone puts host coverage at
**99.1%**, so `tests.yml`'s threshold moves 83 → 99.

Workflows: `main.yml` deleted as a straight duplicate of `tests.yml`, `security.yml` folded into it
(`composer validate --strict`, `composer audit --locked`, `pint --test`), `release.yml` added, and
the §6.2 zero-diff check added to `install.yml`.

Still open from §3.9: package repos are not yet thin callers of reusable workflows in
`liberusoftware/.github`. That is step 4 work, alongside the testbench migration.

**Still open:** the host `Modules`/`Themes` testbench failure, resolved by §3.8's suite deletion
(step 3). Until then the working invocation is the three named suites, not a bare `vendor/bin/pest`.

**Step 4 is done, and the rule count finally moved: 15 → 8, not §3.8's 6.**

All 44 packages are on `liberusoftware/package-testbench ^1.5`. None ships a test bootstrap, a
metadata test or a provider test; each `phpunit.xml` declares a `Boundary` suite pointing into
`vendor/liberusoftware/package-testbench/tests/Boundary/{Module,Theme}`, and each `tests.yml` is a
thin caller of the three reusable workflows — closing §3.9's remaining half. The migration is
`scripts/migrate-testbench`, which is idempotent, so it is re-runnable rather than a one-off.

**All 44 suites are green standalone.** The eight surviving host rules are the whole-graph ones,
which no single package could run: standalone installability, the two enablement rules, theme parent
resolution, Composer owning every autoload boundary, one Composer vendor, every declared Filament
plugin class existing, and cross-package namespace dependencies. Host: 262 passed, 12 skipped.

The count is 8 rather than 6 because the extra two are §3.8's own additions, kept: they read the
whole fleet at once and are exactly what the testbench cannot see from inside one package.

**Four testbench releases, each forced by something a package suite caught rather than something the
plan predicted:**

- **1.3.0** — the three rules that cover one kind of package each now decide that in a `skip()`.
  As guard clauses they returned early, so every domain module in the fleet ran two tests that
  passed having asserted nothing.
- **1.4.0** — a package's version must be `MAJOR.MINOR.PATCH`. `Manifest::fromFile()` requires the
  key and never checks its shape; this was the one assertion in the seven deleted host rules that
  nothing here replaced.
- **1.4.1** — **1.3.0 and 1.4.0 shipped a module suite that could not run.** The skip conditions were
  static closures, which Pest binds to the test case, and every module failed those three tests
  outright. It shipped because the testbench's own `phpunit.xml` excluded `tests/Boundary` on the
  grounds that the package is not a module. It is not, but a throwaway fixture is: the suites now run
  in their own repository against fixture packages. **The one thing a package exists to distribute is
  the one thing its own CI must execute.**
- **1.5.0** — `PackageTestCase` boots the providers a package's dependencies declare, because
  Testbench runs no package discovery. `extra.laravel.providers` of a direct requirement is Laravel's
  own discovery; a sibling module in `require-dev` is booted too, and one in `require` deliberately
  is not — their manifests declare that array empty precisely so installing one never boots it.

**Three packages could not boot standalone at all, and none of it was new.** Each had a hand-written
`TestCase` that papered over it, so the defect was invisible until the shared bootstrap replaced
those files (all three are now §4 rows):

- both `*-livewire` modules needed `LivewireServiceProvider` and declared no way to get it;
- `localization-mymemory` boots against a `TranslationProviderRegistry` implementation nothing in its
  dependency graph binds — its old `TestCase` supplied an anonymous fake. It now `require-dev`s
  `localization-core`, keeping the adapter free of the implementation at runtime;
- `theme-support` threw `The tracked themes directory is missing` while *registering*, so it could
  not boot in any application without a `themes/` tree, including its own test application. Discovery
  now yields no themes instead of throwing, and the fallback and inheritance guards fire only once
  themes exist. A composition that loses its themes is still caught where that is knowable — the
  host's own rules read `themes/` directly.

**Step 5 has started, and §3.7's actor is not the one it describes.**

`package-testbench` 1.6.0 ships `TestUser`, its factory, the base `users` migration and a
`UsesTestUser` trait. It implements **none** of the four actor contracts. Doing so would make the
testbench require Horizon, Pulse, Telescope, Jetstream, Socialite and Socialstream — in all 44 dev
trees — for four one-method interfaces. Only three host tests touch those contracts, and all three
assert on the host's own `User`, so they are composition tests either way. What the movable tests
actually needed was the base `users` table, which is exactly the thing no package owns: `profiles`
adds `locale`, `theme_preference` and `timezone` to it, `search` adds its indexes, but the table
belongs to the application.

Moved so far: `settings` takes the three site-settings tests, `localization-core` takes the locale
middleware suite. Host: 253 passed, 12 skipped.

**Two families cannot move yet, for reasons worth recording rather than retrying:**

- **Themes.** `ThemeManager*` and `ThemeSwitcherTest` assert against installed themes, and themes are
  discovered from `base_path('themes')` rather than through Composer the way `ModuleDiscovery`
  resolves modules from `InstalledVersions`. A package therefore cannot see a theme it dev-requires,
  because Testbench's base path is its own skeleton. Making theme discovery Composer-driven would fix
  this and the §4 `theme-support` row together; it is a package redesign, not a test move.
- **Search.** `SearchServiceTest` and the six HTTP search tests depend on the host's post and group
  fixtures — the fixtures for exactly the methods that leave for `search-demo`. Moving them first
  would relocate scaffolding for code that is about to be deleted, so search follows that task.

**Both blockers are now cleared, and clearing them changed two packages' designs.**

- **Theme discovery goes through Composer.** `ThemeDiscovery` merges the tracked `themes/` tree with
  `InstalledVersions::getInstalledPackagesByType('liberu-theme')`, deduping by `realpath` before the
  name check so a theme present both ways is one theme. That mirrors `ModuleDiscovery` and is what
  §3.6 meant all along. It also **retires all three loosened guards** from the `theme-support` §4 row
  above: a missing `themes/` directory is no longer a hole in discovery, so the fallback and
  inheritance checks are strict again. The switcher tests moved to `theme-support-livewire`, verified
  from a real resolve after `theme-support` published — not from a patched `vendor/`.
- **Search gained a registry.** `SearcherRegistry` holds `type => callable`; `searchAll()` iterates
  it and names nothing, and `search-api` derives its accepted `types` from the same registry. The
  `search` package registers only `users`. Naming post and group in `searchAll()` was the actual tie
  to the demo — deleting the methods without this would have left the coupling behind in the shape of
  a match arm. An unregistered type is now *absent* from the result rather than present and empty, so
  a caller can tell "this composition has no such type" from "no matches".

**Both are now delivered, and each of the five repositories is green on its own suite.** All five
were cloned to `~/code/<repo>`, migrated onto the testbench with `scripts/migrate-testbench`, which
now takes explicit paths for exactly this, and released as **1.1.0**:

| Repository | Suite | What it took |
| --- | --- | --- |
| `module-search-demo` | 50 passed, 2 skipped | the whole demo surface, plus six adopted host suites |
| `module-messaging` | 11 passed, 2 skipped | nothing beyond the testbench |
| `module-messaging-api` | 21 passed, 2 skipped | `laravel/sanctum` dev-required; one real defect fixed |
| `module-blog-core` | 7 passed, 2 skipped | nothing beyond the testbench |
| `module-blog-filament` | 7 passed, 2 skipped | a fixture panel and a widened provider walk |

Two plan assumptions failed on contact: those four repositories were never renamed as §3.4 predicted
(still `module-messaging`, `module-blog-core`) and were still at 1.0.4 on the pre-testbench
bootstrap. Both are recorded rather than retried — the rename is a §3.3 question, not a step-5 one.

**Four things only surfaced once each package was tested on its own dependencies.**

- **`MessageController::users()` selected `profile_photo_path`** — Jetstream's column, which nothing
  in `messaging-api`'s dependency graph creates. `/api/messages/users` had returned a 500 for four
  releases in any application without Jetstream, and was invisible because the only thing exercising
  it was a host that happened to have Jetstream. Fixed by narrowing the select; two of the adopted
  cases now cover it. **This is the clearest evidence for §3.8's premise** that a package must run
  against its own tree, not a composition's.
- **`search` has an unwritten contract with the user model.** `SearchService::searchUsers()` calls
  `search()` and `role()` on whatever `search.models.user` names, and ships no trait supplying them —
  the scopes live on the host's own `User`. `search-demo` stands one up in `tests/Fixtures`. A trait
  in `search` is the real fix and is not done.
- **A package that extends a dependency's registry must boot it.** `search-demo`, `messaging-api` and
  `blog-filament` each name a `require` sibling's provider in `getPackageProviders()`. Listing the
  same package in `require` *and* `require-dev` also works and was the first design, but it earns a
  `composer validate` warning for a duplication that says nothing true. The testbench's rule stands:
  a runtime requirement is not a statement about what the package is tested against.
- **`blog-filament` is the first package in the fleet to boot a Filament panel in its own suite**, so
  it needed a fixture panel and every `extra.laravel.providers` in the installed tree — the testbench
  registers only *direct* dependencies', and the Filament stack is transitive. That widened walk is
  deliberately local until a second `-filament` package needs it; **that is when it moves to
  `package-testbench`**, not before.

Two adopted tests could not have failed as written and were rewritten rather than copied: the blog
index case asserted `assertOk()` without ever creating a post, and `BlogResourceCoverageTest`
rendered an empty table, which cannot fail on a broken column.

The host's `tests/Fixtures/Models/{Post,Group}`, their factories and the fixture migration are
deleted — no host test had referenced them since the demo methods left, and the suite is unchanged at
203 passed, which is the evidence.

**§3.9's shape was wrong in one respect: the three are three *workflows*, not three jobs.**

As authored, each package ran one `tests.yml` calling all three reusables — four jobs per push. A
publish sweep pushes 44 repositories inside twenty seconds, so one sweep queued **176 jobs**. Fixed
three ways: separate workflow files; only `tests` on push, with `install` and `compatibility` on
`[0-9]+.[0-9]+.[0-9]+` tags, because resolving from nothing and resolving lowest are release
questions rather than per-commit ones; and a `concurrency` group keyed on workflow + ref so a
re-publish supersedes its own unfinished sweep. One sweep is now 44 jobs.

**The change stands; the evidence that prompted it does not.** It was adopted after observing the
organisation's queue stall — hundreds queued, none running, and finally pushes creating no runs at
all. That was **not** job amplification. GitHub opened a critical Actions incident at 15:22 UTC on
2026-08-06 and throttled webhook triggers to ~15%, which is exactly "a push creates no run", with
roughly a third of queued jobs failing outright. Every observation behind the diagnosis falls inside
that window. 176 jobs per sweep is still four times more than the work justifies, so the fix is kept
on its own arithmetic — but **package CI has not been observed green on the current fleet**, and it
cannot be until the incident clears. Nothing downstream should read a green package badge into this.

`package-install.yml` also dropped `--strict` from `composer validate`. It promotes warnings to
errors, and one warning is that a published package should omit `version` — which this fleet cannot
do, because `ModuleValidator` compares each manifest against `Composer\InstalledVersions` and the
boundary suite asserts `composer.json` and `module.json` agree on it. `--strict` failed all 44
packages for carrying a field they are required to carry.

## 5. Migration sequence

[Sequence the package migration](https://github.com/liberusoftware/boilerplate-laravel/issues/622)

**Governing principle: do every cross-cutting change while it is still one atomic commit.** In the
monorepo a change touching 40 packages is one diff and one green run; after the flip it is 40
coordinated releases. So the split is **last**, and each package is created in its final form and
published exactly once.

**One diff, but not one green run.** Step 2 proved the second half of that claim false. `modules/`
is a Composer *install target*, so `vendor/composer/installed.json` owns the autoload map, and it
was written from each package's *published* composer.json. File contents under `modules/*/src` are
read live through that map, but the psr-4 prefixes and package names in it are frozen until a
reinstall — and a reinstall fetches from GitHub, discarding the local edit. So the monorepo can
change what a class *does* and still be green, but the moment it changes what a class is *called*
or what package owns it, the host cannot boot until the wave is published.

Confirmed twice: applying the analytics namespace rename gave `analytics-core: provider
Liberu\Analytics\Core\AnalyticsServiceProvider is not autoloadable`, and
`composer update --dry-run liberusoftware/identity-core` gave *"could not be found in any
version"*. The atomic-commit principle still holds for authoring; it does not hold for verifying.

Practical consequence for steps 2 and 3: **the gate is each package's own suite, not the host's.**
A package suite boots Testbench against the files on disk and never consults `installed.json`, so
it is the only check available before publish. A package whose dependency was renamed in the same
wave cannot even install standalone — that is expected, not a defect, and it clears when the wave
lands. The host suite becomes a gate again only once the wave is published and root is repointed,
which is why step 2 now ends in a release wave rather than a green host run.

| Step | Work | Gate |
| --- | --- | --- |
| **−1** | Installer: four fixes (§4), publish, repoint root | clean locked install places every package — **done** |
| **0** | Exile the six out-of-scope packages via `publish-components`; strip from root, config and host tests | host green at 38 modules + 4 themes — **done**, see §4 |
| **1** | Author `package-testbench` (three suites, then v1.1 actor) ∥ reusable workflows in `liberusoftware/.github` | testbench suites green — **done**, shipped through 1.7.0 |
| **2** | **All renames**, in the monorepo: `-core`, `module-*-{surface}`, `foundation-filament` dissolve, `theme-support` Livewire split, analytics namespace, `RolesPermissions`, categories, `default_enabled` | every package's own suite green, or blocked only on a same-wave rename — **done**, published as 1.1.0, see below |
| **3** | Host: manifest-derived `config/modules.php`, `phpunit.xml`, architecture rules 12→6, `ThemeColors` into `app/`, workflow changes | host green — **done**, see below; rules went 12→15, not 12→6 |
| **4** | Migrate every package onto the testbench and the three workflows; add `config.allow-plugins` | every package suite green — **done**, see below; rules finally went 15→8 |
| **5** | Redistribute the 9 clean + 16 actor-dependent host tests into their owning packages | host and package suites green — **done**, see below; five repositories released as 1.1.0 |
| **6** | Re-measure coverage; set each repo's ratchet threshold | thresholds recorded |
| **7** | **Pilot one leaf package** end to end: split, CI green, publish, require, `composer update` | **zero `modules/` diff** |
| **8** | Remaining waves, leaves before dependents | zero diff per wave — the §6.2 check is already enabled and green, from step 3 |
| **9** | Remove `publish-components` and `audit-divergence` | **one fleet-wide change released end to end through `fleet`** — **done**: the 1.4.0 coverage-ratchet wave, §6.2 clean from a real install afterwards |

The pilot works because `modules/<name>` is the same path whether committed source or Composer
output: a package flips by being published, required and `composer update`d into the path it already
occupies. Provenance changes; the path does not.

**Rollback.** Steps −1 to 6 are ordinary reverts; only the installer and testbench are published,
both additive. Step 7 rolls back by removing one package from `require` and restoring its directory.
Step 8 rolls back per wave, with the lockfile as authority. **Step 9 is the point of no return** —
tested when it was priced, and upheld: the archive itself un-archives, but reversal means
re-flattening 44 independent histories into one tree, which is the divergence audit run backwards.

**What of `scripts/` survives step 9.** Only the two tools built for the monorepo publish loop went.
The `liberusoftware/boilerplate-scripts` **package is not archived**: decision 8 was taken when that
package *was* the publish loop, and it now carries `fleet` — archiving it would retire the
replacement along with the thing replaced. Released as `1.2.0` without the two tools.

| | |
| --- | --- |
| `publish-components` | **removed** — it rsynced this monorepo *into* the package repositories, the opposite direction to the flip |
| `audit-divergence` | **removed** — its question, *does the tracked tree differ from what is published?*, stops being askable once `modules/` is Composer output, and §6.2 answers it continuously in CI |
| `fleet` | the replacement |
| `migrate-testbench` | kept — takes explicit paths, so it runs against checked-out package repositories as a `fleet run` payload |
| `submit-packagist.php` | kept — first publication is still a manual submission, unrelated to the publish loop |
| `setup.sh`, `update.sh` | kept — they install and release the host application, not the packages |

## 6. ADR exceptions

**None.** Every case where an exception was available was resolved in favour of a mechanically
checkable rule instead:

- `application-core` lost its suffix rather than earning a carve-out.
- `module-module-manager-filament` keeps its stutter rather than excepting the naming rule.
- The strict namespace rule was rejected on **merit** — it would force `Liberu\Foundation\AnalyticsCore`, corrupting what the namespace means — not to avoid work.

One obligation was recorded instead of an exception: the documentation repo specified the `liberu/`
vendor while this repo keeps `liberusoftware/` (§3.2). That was a docs PR, not a local exception, and
it is now filed — [liberusoftware/documentation#16](https://github.com/liberusoftware/documentation/pull/16),
64 references across 5 files.

It turned out not to be a conflict between the standard and this repo at all. `PROMPT.md` already
mandated `liberusoftware/` in two places, so the standards repo disagreed with **itself**; two of the
64 named packages that only exist under `liberusoftware/`, making §10.1 unfollowable as written. And
`liberu/` was never available to adopt — Packagist protects a vendor once anything publishes under it,
and `liberu/laravel-gramps-xml` belongs to another account ([#627](https://github.com/liberusoftware/boilerplate-laravel/issues/627)).

## 7. Evidence

- Prototype: branch [`prototype/package-testbench`](https://github.com/liberusoftware/boilerplate-laravel/tree/prototype/package-testbench) — throwaway, do not merge.
- Coverage baseline and per-package table: [Measure per-package coverage](https://github.com/liberusoftware/boilerplate-laravel/issues/618).
- All decisions and their reasoning: [the map](https://github.com/liberusoftware/boilerplate-laravel/issues/612).
