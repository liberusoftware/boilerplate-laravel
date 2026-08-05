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

**Changes to:** `modules/` becomes Composer output. `publish-components` becomes a one-time splitter, then archived.

**Enforced by:** the §6.2 clean-install zero-diff check in the host's `install.yml`.

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
> Incidental: `activity-comments`, `clear-signal`, `module-manager` and `theme-support` ship a
> **committed `vendor/` directory** upstream. Anything pushing to those repos must stage named
> paths — `git add -A` there commits an entire dependency tree.
>
> Re-measure with `scripts/audit-divergence`; results land in `storage/app/divergence.tsv`.
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
> Still outstanding: the fleet is fixed on `main` but **not re-released**. Until each package is
> tagged, `composer update` still resolves the broken `1.0.4`.

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
callers of **reusable workflows in `liberusoftware/.github`**. Rule 20 (each repo owns its workflows)
and CI.md (a reusable workflow owns repeated setup) reconcile: a callable Actions workflow is not a
Composer package.

**Host:** delete redundant `main.yml`, fold `security.yml` into `tests.yml`, add `release.yml`, and
put the §6.2 zero-diff check in `install.yml`. `deploy-staging.yml` is not added — CI.md marks it optional.

### 3.10 Coverage

[Measure per-package coverage](https://github.com/liberusoftware/boilerplate-laravel/issues/618)

Measured baseline: fleet **15.1%** (193/1281 lines), median package **12.82%**, **4 Filament
packages at 0%**, 26 of 40 under 15%.

Per `TESTING.md` §13 — "a threshold below 100% is a migration state, not a policy" — each repository
sets its initial CI threshold from its **step-6** measured figure, not the pre-migration figure
above. Do not set a 0% floor for the four Filament packages; give them a first real test instead.

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

**Fixed in this repository** — the four that live here, verified rather than asserted:

- **`config.allow-plugins`** now declared by all 48 packages, pinned by a new architecture rule. `composer update` in `modules/search`, `modules/analytics-{core,google,meta}`, `modules/module-manager` and `themes/dark` succeeds **without `--no-plugins`**, and each package's suite then runs green standalone.
- **`analytics-core`** no longer imports `Analytics\Google\*` / `Analytics\Meta\*`; those assertions moved into the adapters' own suites, so no coverage was lost.
- **`module-manager`'s `ManifestTest`** reads its own `module.json` instead of globbing the consuming application. The fleet-wide parse moved into the host's existing package-metadata rule rather than becoming a new one.
- **Both broken architecture rules** work: the `Architecture` suite now boots the app for `config('modules.*')`, and the theme-parent rule throws with the offending theme named. Each was mutation-tested — deliberately violated and confirmed red — because both were previously green by accident.

**Fixed in `liberusoftware/composer-installer`** — branch `fix/installer-defects`, unpublished. `plugin-modifies-install-path` declared; collision detection now reads the working tree, so a directory whose `composer.json` names a different package fails the install instead of being written over. A real `composer install` was used to confirm both, and all 48 packages in this tree still resolve to their current paths. One consequence for §5 step 8: a package that keeps its directory while changing its Composer name now fails until the old directory is removed — by design, but it makes a rename `rm -rf` the old target, then install.

**`composer.lock` is no longer stale.** A root `composer install` reports "Nothing to install, update or remove" with no lock warning, and `composer validate` passes. Note this does **not** exercise §6.2: Composer no-ops when the installed state already matches the lock. The zero-diff gate needs a *clean* install, which is the path that reinstalls all 48 packages over the tracked source — still red until step 8.

**Blocking, found while starting step 0:** every one of the 48 packages has diverged from its
published repository, and the monorepo is the side that is behind. See the correction in §3.1. No
package may be published from here until that is reconciled, because `publish-components` mirrors
with `--delete`.

**Still open:** the host `Modules`/`Themes` testbench failure, resolved by §3.8's suite deletion (step 3).

## 5. Migration sequence

[Sequence the package migration](https://github.com/liberusoftware/boilerplate-laravel/issues/622)

**Governing principle: do every cross-cutting change while it is still one atomic commit.** In the
monorepo a change touching 40 packages is one diff and one green run; after the flip it is 40
coordinated releases. So the split is **last**, and each package is created in its final form and
published exactly once.

| Step | Work | Gate |
| --- | --- | --- |
| **−1** | Installer: four fixes (§4), publish, repoint root | clean locked install places every package |
| **0** | Exile the six out-of-scope packages via `publish-components`; strip from root, config and host tests | host green at 38 modules + 4 themes |
| **1** | Author `package-testbench` (three suites, then v1.1 actor) ∥ reusable workflows in `liberusoftware/.github` | testbench suites green |
| **2** | **All renames**, in the monorepo: `-core`, `module-*-{surface}`, `foundation-filament` dissolve, `theme-support` Livewire split, analytics namespace, `RolesPermissions`, categories, `default_enabled` | host green; nothing published |
| **3** | Host: manifest-derived `config/modules.php`, `phpunit.xml`, architecture rules 12→6, `ThemeColors` into `app/`, workflow changes | host green |
| **4** | Migrate every package onto the testbench and the three workflows; add `config.allow-plugins` | every package suite green |
| **5** | Redistribute the 9 clean + 16 actor-dependent host tests into their owning packages | host and package suites green |
| **6** | Re-measure coverage; set each repo's ratchet threshold | thresholds recorded |
| **7** | **Pilot one leaf package** end to end: split, CI green, publish, require, `composer update` | **zero `modules/` diff** |
| **8** | Remaining waves, leaves before dependents; enable the §6.2 check | zero diff per wave |
| **9** | Archive `boilerplate-scripts` and `publish-components` | — |

The pilot works because `modules/<name>` is the same path whether committed source or Composer
output: a package flips by being published, required and `composer update`d into the path it already
occupies. Provenance changes; the path does not.

**Rollback.** Steps −1 to 6 are ordinary reverts; only the installer and testbench are published,
both additive. Step 7 rolls back by removing one package from `require` and restoring its directory.
Step 8 rolls back per wave, with the lockfile as authority. **Step 9 is the point of no return.**

## 6. ADR exceptions

**None.** Every case where an exception was available was resolved in favour of a mechanically
checkable rule instead:

- `application-core` lost its suffix rather than earning a carve-out.
- `module-module-manager-filament` keeps its stutter rather than excepting the naming rule.
- The strict namespace rule was rejected on **merit** — it would force `Liberu\Foundation\AnalyticsCore`, corrupting what the namespace means — not to avoid work.

One obligation is recorded instead of an exception: the documentation repo specifies the `liberu/`
vendor while this repo keeps `liberusoftware/` (§3.2). That is a docs PR, not a local exception.

## 7. Evidence

- Prototype: branch [`prototype/package-testbench`](https://github.com/liberusoftware/boilerplate-laravel/tree/prototype/package-testbench) — throwaway, do not merge.
- Coverage baseline and per-package table: [Measure per-package coverage](https://github.com/liberusoftware/boilerplate-laravel/issues/618).
- All decisions and their reasoning: [the map](https://github.com/liberusoftware/boilerplate-laravel/issues/612).
