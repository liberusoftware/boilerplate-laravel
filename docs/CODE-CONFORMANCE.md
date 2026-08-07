# Code-level conformance audit

Where this fleet stands against the code-level standards in
[liberusoftware/documentation](https://github.com/liberusoftware/documentation) `standards/`.

**This document fixes nothing.** Each finding is its own decision, taken afterwards with the
measurement in hand. Charted as
[Wayfinder: audit boilerplate-laravel against the Liberu code-level standards](https://github.com/liberusoftware/boilerplate-laravel/issues/642);
the predecessor effort's structural conformance is `CONFORMANCE.md`.

## How to read a finding

A finding is one **rule**, the **population** it fails in, and the **size** of that population. A rule
violated in 400 files is one row with a count, not four hundred rows. The fourth column is **cost** —
the shape and size of the work — not a prescribed fix, because prescribing the fix would pre-empt the
decision this document exists to inform. `unknown` is a legitimate cost.

| Rank | Test |
| --- | --- |
| **breaks a consumer** | someone who installs or uses the package hits it |
| **breaks a promise** | the repository asserts something untrue — a README, a manifest, `CLAUDE.md`, `CONFORMANCE.md` |
| **unenforced** | the standard demands a check or property nothing guarantees; not necessarily wrong today |
| **style only** | violated, no behavioural consequence |

There is no *breaks a test* rank. The fleet's CI is green, so anything that broke a test would
already be red and would be a bug report rather than an audit finding. Settled in
[Define what a finding is, and how findings rank](https://github.com/liberusoftware/boilerplate-laravel/issues/645).

**⚠ security** is a flag, not a rank. The four ranks describe *realized* consequence — what is
broken, what is untrue, what is ungated, what is cosmetic. A supply-chain exposure has no realized
consequence; it is risk, and *unenforced* is already the risk bucket. The flag records impact within
that bucket without asserting a false ordering between "arbitrary code execution someday" and "a
consumer breaks today". Settled in
[Does the ranking need a security rank](https://github.com/liberusoftware/boilerplate-laravel/issues/651).

**Rank compares across the document; cost separates within a class.** A class whose rules share one
failure mode will legitimately show one rank — see the CI class below, where all 17 findings are
*unenforced* and the cost column runs from "2 lines" to "unknown".

## How the audit is sliced

Not per standard. Every rule in the corpus was classified by the **mechanism that could catch it**,
and the classes cut across the files — `FILAMENT.md`, `LIVEWIRE.md` and `THEMES.md` hold most of one
class between them while `TESTING.md` holds a quarter of another. The full per-standard tables are in
[the rule classification](https://github.com/liberusoftware/boilerplate-laravel/blob/research/standards-classification/docs/research/standards-rule-classification.md).

| Class | Clusters | Audited |
| --- | --- | --- |
| **Boundary** — a package can check it about itself | 34 | **yes, below** |
| **CI** — decidable by a workflow step | 30 | **yes, below** |
| **Arch** — whole-graph, needs every package in view | 11 | **yes, below** |
| **Larastan** | 6 | **yes, below** |
| **Pint** | 3 | **yes, below** |
| **Prose (fleet)** — a standing property the audit can state | 38 | **yes, below** |
| **Prose (per-change)** | 104 | **out of scope** — an obligation on the next change, with no fleet-wide fact behind it. An audit row for one cannot fail |

## Standard status

Measured against this tree on 2026-08-07: 40 `liberu-module` packages under `modules/`, 4
`liberu-theme` packages under `themes/`, and the host.

`—` means the standard states no rule in that class. A blank cell would be ambiguous between "clean"
and "nobody looked", which is the confusion this table exists to prevent.

| Standard | Boundary | CI | Findings so far |
| --- | --- | --- | --- |
| `ADOPTION` | — | — | 0 |
| `API` | — | audited | **2** |
| `BLADE` | — | audited | 0 |
| `CI` | — | audited | **6** |
| `CLASSES` | — | — | **1** |
| `CONCERNS` | — | — | 0 |
| `CONTRACTS` | — | — | **1** |
| `CONTRIBUTING` | — | audited | 0 — its heading and link rules become checkable with the Markdown linter `DOCUMENTATION` asks for |
| `CONTROLLERS` | — | — | 0 |
| `DATABASE` | audited | audited | **2** |
| `DOCUMENTATION` | — | audited | **3** |
| `DOMAIN-DRIVEN-DESIGN-PATTERNS` | — | — | — |
| `FILAMENT` | audited | audited | **4** |
| `FRONTEND-TESTING` | vacuous | vacuous | — no JavaScript presentation package exists |
| `GUIDELINES` | — | — | — |
| `JOBS` | vacuous | vacuous | — the fleet ships **no job classes at all** |
| `LARAVEL` | audited | — | 0 |
| `LIVEWIRE` | audited | vacuous | **2** — no SFC/MFC and no islands, so both CI rules have an empty population |
| `MODELS` | audited | — | 0 — see the `DATABASE` findings |
| `OBJECT-ORIENTED-PROGRAMMING` | — | — | — |
| `PHP` | — | audited | **3** |
| `PINT` | audited | audited | 0 |
| `PSR` | audited | audited | **2** |
| `QUEUES` | vacuous | — | — no job classes |
| `README` | index only | — | — |
| `SERVICES` | — | — | — |
| `TESTING` | audited | audited | **4** |
| `THEMES` | audited | audited | **5** |
| `TRANSLATIONS` | — | audited | **2** |
| `VIEWS` | — | audited | 0 |
| `FLUTTER` `INERTIA` `MOBILE` `NUXT` `REACT` `REACT-NATIVE` `VUE` | **inapplicable** | **inapplicable** | this stack has no React, Vue, Nuxt, Inertia, Flutter, React Native or mobile surface. Ruled out while charting, recorded here so the seven are not re-litigated |

## Boundary class — findings

| Rule | Population | Rank | Cost |
| --- | --- | --- | --- |
| **Livewire component aliases are not package-qualified.** `LIVEWIRE.md` §3 requires `module-{scope}::{component}`; both packages register a bare global name — `Livewire::component('language-switcher', …)` and `'theme-switcher'`. §3 also requires duplicates to fail rather than last-write-wins; a bare alias silently loses to any consumer registering the same name | 2 of 2 `-livewire` packages | **breaks a consumer** | 2 packages + every mount site. Cheap today: `CLAUDE.md` records that `<livewire:language-switcher />` is not mounted anywhere yet |
| **`identity-core-filament` does not depend on the module it is named for.** `FILAMENT.md` §3.1 requires a one-to-one match with its domain module. It requires `roles-permissions` and **not** `identity-core`. Its `UserResource` resolves the model from `config('auth.providers.users.model')`, so it presents a model no module in its dependency graph owns | 1 of 6 `-filament` packages | **breaks a promise** | 1 package, a `require` line; unknown whether the resource then belongs there at all |
| **Composer basename and installer name lack the `module-` prefix.** `FILAMENT.md` §2 and `LIVEWIRE.md` §2 apply the rule to "the independent GitHub repository name, Composer package basename, Liberu installer name, and installed directory". The repositories carry it; the Packagist names and installed directories do not. `CONFORMANCE.md` §6 states **"ADR exceptions: None"** while this exception is in force and undocumented | 8 presentation packages — 6 `-filament`, 2 `-livewire` | **breaks a promise** | either 8 renames + a release wave and every consuming `composer.json`, or one ADR. The naming decision itself is `CONFORMANCE.md` §3.3 |
| **Plugin IDs do not equal the installer name.** `FILAMENT.md` §2: "Plugin IDs are stable kebab-case identifiers equal to the installer name". Measured: `liberu-identity` / `identity-core-filament`, `liberu-organizations` / `organizations-teams-filament`, `liberu-settings` / `settings-filament`, `liberu-sessions-devices` / `sessions-devices-filament`, `liberu-module-manager` / `module-manager-filament` | 5 of 6 `-filament` packages — the sixth inherits Shield's ID | **style only** | nothing breaks today, but a plugin ID is public surface: changing five is a major release each |
| **Three packages migrate a table none of them owns.** `profiles`, `search` and `identity-socialstream` all `Schema::table('users', …)`. `DATABASE.md` forbids depending on migration order from another independent package without an explicit dependency contract; all three declare `identity-core`, which does not create `users` either. Ordering between them is currently accidental — `search` 2026-02-14, `profiles` 2026-02-16, `identity-socialstream` 2026-06-29 | 3 of 40 modules | **unenforced** | unknown. `CLAUDE.md` already records this as deliberate: `users` "is the one thing no package owns" |
| **Four of five Composer script aliases missing.** `THEMES.md` §3.1.1 and `TESTING.md` §14 require `test`, `test:unit`, `test:feature`, `test:coverage`, `test:parallel`. Every package declares `"test": "pest"` and nothing else | 44 of 44 packages, and the host | **unenforced** | 44 `composer.json` edits + a release wave; mechanical |
| **Themes do not declare `pestphp/pest-plugin-laravel`.** `THEMES.md` §3.1.1 names it in the canonical `require-dev`. All four declare `package-testbench` + `pest` only | 4 of 4 themes | **unenforced** | 4 `composer.json` edits + a release wave |

## Boundary class — conformance

Recorded because silence is not evidence. The predecessor effort's two largest findings — coverage,
then static analysis — were both silence mistaken for health.

| Rule | Evidence |
| --- | --- |
| One class, interface, trait or enum per file (`PSR.md`) | **216 source files, 0 violations** |
| No package ships a `PanelProvider` (`FILAMENT.md` §7) | **0 of 44** — panels stay in the host |
| Discovery is bounded; nothing scans the app, `/modules`, `/themes` or `/vendor` (`FILAMENT.md` §9.1) | **0** `discoverResources`/`discoverPages`/`discoverWidgets` calls anywhere |
| Plugin class is final and offers `make()` (`FILAMENT.md` §8) | **6 of 6** final; **6 of 6** declare `make()` |
| `phpunit.xml` committed (`TESTING.md` §3) | **44 of 44** |
| `pestphp/pest ^5.0` in `require-dev` (`TESTING.md` §3) | **44 of 44**, exact constraint |
| `phpunit/phpunit` **not** required separately (`TESTING.md` §3) | **0 of 44** do — though **the host does**, at `^13.1.8`. That is a CI-class row, audited later |
| Manifest, provider, parent chain, cycles and asset paths validated (`THEMES.md` §18) | enforced continuously by `ThemeBoundaryTest` in `liberusoftware/package-testbench`, run by every theme against its own files |
| Themes own no functional widgets (`FILAMENT.md` §12) | **0** widget classes in theme source. A recursive search of `themes/` matches 18, but every one is inside an untracked local `vendor/` left by running a theme's suite — scope the check to `themes/*/src` |
| Composer type and install path (`FILAMENT.md` §4) | **6 of 6** `-filament` declare `liberu-module`; installer names match their directories |

## Recorded deviations, not findings

- **`roles-permissions-filament` extends `FilamentShieldPlugin`** rather than implementing Filament's
  plugin contract directly, and inherits Shield's plugin ID. `FILAMENT.md` §8 asks for one final
  plugin class implementing the contract. This is deliberate and already documented in `CLAUDE.md`:
  the module wraps Shield with tenant resource scoping disabled, because Spatie already isolates
  roles by `team_id`. The class is still `final`.
- **`JOBS.md` and `QUEUES.md` boundary rules are vacuous here.** The fleet ships no job classes, so
  "declare explicit connection, queue, timeout, `tries`, `backoff`, `maxExceptions`" has nothing to
  bind to. Recorded rather than reported as conformance, because passing a rule with an empty
  population is not evidence of anything.

## CI class — findings

Rules that are mechanically decidable but by a workflow step rather than by Pint or Larastan. All 17
findings rank **unenforced**, and that is not a coincidence — see *Where the ranks strained* below.
Two carry the **⚠ security** flag.

| Rule | Population | Rank | Cost |
| --- | --- | --- | --- |
| **Third-party actions are not pinned to full-length commit SHAs.** `CI.md`, *Workflow security*: "Pin third-party actions to full-length commit SHAs". Every reference is a mutable tag — `actions/checkout@v5`, `shivammathur/setup-php@v2`, `actions/cache@v4`, `codecov/codecov-action@v5`, four `docker/*` actions | **21 references** across 4 host workflows and 3 reusable workflows | unenforced **⚠ security** | 7 files, one PR each; then a renewal process, because pinned SHAs need updating |
| **All 44 package callers pin the reusable workflow to `@main`.** A push to `liberusoftware/.github` changes every repository's CI instantly, with no staging. Demonstrated in this effort: adding a `--config` flag pointing at a not-yet-tagged file would have failed all 44 builds, and needed a guard commit rather than a rollback | 44 of 44 | unenforced **⚠ security** | 44 files to pin, or a release process for the reusable workflows |
| **`composer validate` and `composer audit` run only at tag time for packages.** They are in `package-install.yml`, which triggers on tags and `workflow_dispatch`. `CI.md` requires them on every pull request and every push to `main` | 44 of 44 packages | unenforced | one step each in `package-tests.yml` |
| **Host `install.yml` declares no `permissions:` block.** `CI.md`: least-privilege, "normally starting with `contents: read`". The other three host workflows and all three reusable workflows declare one | 1 of 4 host workflows | unenforced | 2 lines |
| **No secret scanning anywhere.** `CI.md` requires "dependency, secret, container, and supported security scans". Dependency scanning exists — `composer audit --locked` in the host, `composer audit` at package release. The other two do not | host + 44 packages | unenforced | one workflow, fleet-wide via the reusable caller |
| **`docker.yml` builds and publishes without scanning.** `CI.md` assigns it "Build, scan, and publish immutable container artifacts" | 1 host workflow | unenforced | one step |
| **The seven translation checks named by `TRANSLATIONS.md` do not exist.** Key uniqueness, placeholder parity, missing keys, invalid plural forms, encoding, stale keys, locale coverage — the standard names them individually as CI's job | 4 locales, every module owning message keys | unenforced | unknown — no tool is chosen |
| **No pseudo-localization or RTL browser tests.** `TRANSLATIONS.md` requires both to verify expansion, truncation, focus order and screen-reader announcements | fleet-wide | unenforced | unknown |
| **No Markdown lint or link checking.** `DOCUMENTATION.md` §12 asks CI to "lint Markdown, check internal and external links, validate headings and code fences" | host + 44 package READMEs | unenforced | one workflow; `CONTRIBUTING.md`'s heading and link rules become checkable with it |
| **No OpenAPI document exists.** `API.md` and `DOCUMENTATION.md` §9 require reference material generated from or checked against a versioned contract, plus drift and breaking-change detection. The fleet ships an `api-access` module | 1 module | unenforced | unknown — writing the contract is the work, not the check |
| **No `visual.yml` in any theme.** `THEMES.md` §18.1 names four required theme workflows; all four themes ship `install.yml`, `tests.yml` and `compatibility.yml` | 4 of 4 themes | unenforced | 4 repositories + a release wave; the accessibility and visual-regression tooling is the unknown |
| **`test:coverage` does not enforce `--min=100`.** `THEMES.md` §18 specifies the exact command with Clover and HTML output. No theme declares the script at all | 4 of 4 themes | unenforced | trivial to declare, unreachable to satisfy — theme coverage is far below 100 |
| **The package workflow produces no coverage artifact.** `TESTING.md` §13 requires a machine-readable report for quality services and an HTML report for diagnosis, retained as protected CI artifacts. The host uploads to Codecov; no package produces anything | 44 of 44 | unenforced | two steps in `package-tests.yml` |
| **No README coverage or CI-status badge.** `TESTING.md` §13: "The README badge must reflect the default branch and link to its maintained report". READMEs carry PHP, release and licence badges | 44 of 44 | unenforced | 44 README edits; blocked on the artifact row above |
| **No release-scope coverage gate.** `CI.md` and `TESTING.md` §13 make 100% of release scope a release gate. Nothing checks it, and the fleet's per-package ratchet is a migration state by the standard's own words | release process | unenforced | unknown — the gate is meaningless until the ratchet approaches 100 |
| **Nothing checks that a released migration is never edited.** `DATABASE.md` states the rule; it is mechanically decidable by diffing `database/migrations/` against the last release tag | 40 modules | unenforced | one step in `package-tests.yml` |
| **`-filament` READMEs document almost none of the required inventory.** `FILAMENT.md` §20 names ~18 items including the panel matrix, typed configuration options, resource/page/widget inventory, and discovery namespaces. A measured README has 12 headings, none of them these | 6 of 6 `-filament` packages | unenforced | 6 READMEs; blocked on the panel matrix not existing (a Boundary finding) |

## CI class — conformance

| Rule | Evidence |
| --- | --- |
| Least-privilege `permissions:` | **3 of 4** host workflows and **3 of 3** reusable workflows declare `contents: read`; `release.yml` correctly widens to `contents: write` |
| `composer validate` and `composer audit` at release | present in `package-install.yml`; the host runs `composer validate --strict` and `composer audit --locked` on every push. `--strict` is deliberately not used for packages — it errors on the `version` field, which this fleet requires because `ModuleValidator` compares manifests against `Composer\InstalledVersions` |
| CI fails on unexpected `modules/` or `themes/` changes | **enforced** — the §6.2 zero-diff gate in the host's `install.yml` |
| The release workflow tests the commit it deploys | `release.yml` triggers on the tag and uses `--verify-tag`; no rebuild from a different ref |
| No `eval`, `unserialize`, `extract`, or bare `catch (Exception)` in owned source | **0 occurrences** across `modules/*/src`, `themes/*/src` and `app/` |
| Framework cryptography rather than custom primitives | one `md5()` in the fleet, a cache-key hash in `localization-mymemory`, not a security primitive |
| `@csrf` present where forms are | **7 of 7** files containing `<form>` |
| Escaped output by default | **3** `{!! !!}` uses, all in the host: two are `$attributes->merge()` in form components, one is a translation string with link placeholders. None interpolates request input |
| SFC/MFC filenames free of the bolt emoji (`LIVEWIRE.md` §6) | **0** — vacuous, the fleet ships no SFC/MFC |
| Islands not placed inside loops or conditionals (`LIVEWIRE.md` §17) | **0 islands** — vacuous |

## Prose (fleet) class — findings

Judgement, but about a standing property of the fleet — so the audit can state it and the statement
can be wrong. This is the class that needed reading rather than tooling.

| Rule | Population | Rank | Cost |
| --- | --- | --- | --- |
| **Both analytics adapters are inert, and nothing says so.** `analytics-google` and `analytics-meta` register their destination only `if ($this->app->bound(GoogleTransport::class))` / `MetaTransport::class`. **Nothing in the fleet or the host binds either**, so enabling one registers no destination and sends nothing. Their READMEs list the contract file but never state that a consumer must implement and bind it, and `CLAUDE.md` says only that they "need third-party credentials" — they need an implementation as well | 2 of 40 modules | **breaks a promise** | 2 READMEs and one `CLAUDE.md` sentence, if documenting is the answer; a reference transport each if it is not |
| **`CLAUDE.md` describes a domain-doc layout that does not exist.** Its *Domain docs* section declares single-context: "one root `CONTEXT.md` and one `docs/adr/`". Neither path is present. `DOCUMENTATION.md` §3 makes ADRs the source of truth for architectural decisions, and `CONFORMANCE.md` §6 resolves exceptions by pointing at ADRs that have no home | host | **breaks a promise** | either create both, or correct `CLAUDE.md`. The decision is which |
| **11 of 23 declared interfaces have no implementation anywhere** — not in any package, not in the host. `ActivityAuthorizer`, `ExchangeRateProvider`, `GoogleTransport`, `IntegrationAdapter`, `MediaAccess`, `MediaTransformer`, `MetaTransport`, `ReadinessCheck`, `SettingDefinition`, `TransferAuthorizer`, `TwoFactorRecovery`. `CONTRACTS.md`: "Add one when substitution, testing, integration, or versioned extension is real — not for every class." Several are legitimate consumer extension points; others are provider-shaped contracts with no adapter, which `CONFORMANCE.md` §3.3 already acknowledges for `currency-context` | 11 of 23 interfaces, across 9 modules | style only | per interface: keep and document as an extension point, or delete. Each is public surface that must be versioned either way |
| **No RFC 9457 Problem Details anywhere.** `API.md` requires "RFC 9457-compatible errors where defined by the ecosystem". `api-access` produces none, and `bootstrap/app.php` renders `api/*` exceptions in Laravel's default shape | 1 module + host | unenforced | one exception renderer; the contract shape is the decision |
| **Theme tokens do not cover the eight required states.** `THEMES.md` §6: tokens must cover "light, dark, high-contrast, error, warning, success, disabled, and focus". `base` defines error, warning, success, disabled and focus; **high-contrast has no token**. One stylesheet in the fleet — `themes/base/resources/css/app.css` — addresses `forced-colors`, and the same single file addresses `prefers-reduced-motion` | 4 of 4 themes, inherited from `base` | unenforced | tokens in `base`; the three children inherit |
| **100 lines exceed the 120-character limit**, across 50 files. `PSR.md` calls it a **soft** limit — "split long code where clarity improves" — so this is a reading of intent rather than a breach, and no fixer enforces it | 50 of 216 source files | style only | 100 line splits, judged individually; Pint has no line-length fixer |

## Prose (fleet) class — conformance

| Rule | Evidence |
| --- | --- |
| Optional infrastructure bound at composition time (`ADOPTION.md` rule 2) | the analytics adapters are the pattern done correctly — a guarded `bound()` check means a missing capability omits its behaviour rather than throwing. The finding above is that this is undocumented, not that it is wrong |
| Controllers are thin (`CONTROLLERS.md`) | the whole fleet ships **2 controllers**: `SearchController` with 3 public methods, `ReadinessController` with 1 |
| Traits are rare and justified (`CONCERNS.md`) | **2 traits fleet-wide** — `Searchable`, a published extension point, and `PasswordValidationRules`, a Fortify convention. The standard's "show at least two real owners" is a bar for adding one, and the fleet has barely added any |
| One documented cascade-layer order (`THEMES.md` §9) | **4 of 4** themes use `@layer` |
| Consent before analytics scripts (`THEMES.md` §16) | 13 references to consent across the analytics modules |
| Create only the test suites the repository needs (`TESTING.md` §4) | the fleet uses `Feature` (9), `Unit` (8) and `Fixtures` (4) of the nine suites the standard lists. The standard says "Create only suites the repository needs"; the shared boundary suite covers the rest, per `CONFORMANCE.md` §3.7 |

## Tool classes — findings

Pint (3 clusters), Larastan (6) and whole-graph Arch (11), audited together because they share one
property: **once the tool runs, the audit is a report rather than a reading**. All 44 packages now
carry a measured PHPStan level and run both tools in CI, so these figures are continuous rather than
a snapshot.

| Rule | Population | Rank | Cost |
| --- | --- | --- | --- |
| **Seven packages call members their declared type does not have.** Every package capped at PHPStan level 1 fails level 2 on `property.notFound` / `method.notFound`. `jetstream-bridge` is the clearest: it type-hints `Illuminate\Foundation\Auth\User`, then calls `deleteProfilePhoto()`, `teams()`, `$connectedAccounts`, `$tokens`, `$ownedTeams` — members only the *host's* `User` has, through traits the package neither requires nor declares. A consumer whose `User` lacks them gets a fatal, not a diagnostic | 7 of 40 modules | **breaks a consumer** | per package: declare the shape it needs as a contract, or narrow the call. `search` is one line; `jetstream-bridge` is a design question |
| **`search` still declares a return type that resolves to nothing.** `SearchService::searchUsers()` returns `Liberu\Foundation\Search\Services\User` — a bare `User` in the package's own namespace, `class.notFound`. Surfaced by the first ticket on this map and still shipping | 1 of 40 modules | **breaks a consumer** | one `use` statement, or one contract |
| **`theme-support` cannot be analysed above level 1 because booting it throws.** `Error: Theme directory/name collision detected.` during the analysis boot. Its measured level is a floor imposed by a boot-time exception rather than a type ceiling, so its ratchet cannot move until the boot is fixed | 1 of 40 modules | **breaks a consumer** | unknown — why the collision check fires in a standalone context needs diagnosing first |
| **`declare(strict_types=1);` is absent almost everywhere.** `PHP.md` requires it in new executable files and `PSR.md` repeats it. Measured: **3 of 216** package source files and **0 of 5** host files. Pint can enforce it, but `declare_strict_types` is **not in the `laravel` preset**, so the shared `pint.json` would have to add it | 213 of 216 package files, 5 of 5 host files | unenforced | one sweep rewrites 213 files — but strict types change coercion, so this is a **fleet-wide semantic change**, not a formatting one |
| **18 packages stop at level 5, and the wall is missing type hints.** Level 6 is where PHPStan begins reporting them, and it is the modal ceiling across the fleet. `PHP.md` requires "typed properties, parameters, and return values" | 18 of 44 packages | unenforced | the ratchet moves one package at a time; no single change clears it |
| **The service-locator prohibition has no rule, and is violated.** `CLASSES.md` forbids service-locator calls and static mutable state. Measured in package `src/`, excluding providers and presentation: **18** `app()`/`resolve()` call sites and **30 files** importing an `Illuminate\Support\Facades\*` class. No stock PHPStan rule catches this; a custom rule banning them in domain namespaces would | 30 of 216 files | unenforced | a custom PHPStan rule in the testbench, then the call sites |
| **Four whole-graph properties the standards demand have no rule.** The host suite holds 8, and none covers: Livewire alias and full-page route collision (`LIVEWIRE.md` §3), a package silently claiming `/`, `/dashboard` or `/admin` (§10), the Blade-to-Livewire boundary (`BLADE.md`), or Filament's one-to-one module ownership (`FILAMENT.md` §3.1, already a Boundary finding) | host suite | unenforced | one rule each; the route-claim rule is the cheapest and catches a real class of collision |

## Tool classes — conformance

| Rule | Evidence |
| --- | --- |
| PSR-12 formatting (`PINT.md`, `PSR.md`) | **44 of 44** packages pass the shared ruleset, enforced on every push. No package carries a competing `pint.json` |
| Every package is analysable | **44 of 44** measured, **none failed level 0** — no package has an error that no level relaxes |
| Static analysis reaches the whole fleet | 11 packages at level 10, 1 at 9, 1 at 8, 18 at 5, 2 at 4, 1 at 3, 2 at 2, 8 at 1 — all ratcheted, none lowerable |
| Composer owns every autoload boundary; no directory scanning | host architecture rule, passing |
| One Composer vendor across the fleet | host architecture rule, passing |
| Every package installs standalone | host architecture rule, passing |
| Enablement derives from manifests, not a config list | host architecture rule, passing |
| Every declared Filament plugin class exists | host architecture rule, passing |
| Cross-package namespace dependencies declared in Composer | host architecture rule, passing |
| Every declared theme parent resolves | host architecture rule, passing |
| No dynamic properties | caught by the levels; **0** across the fleet |
| Database access in controllers · live models in job constructors | **vacuous** — 2 controllers fleet-wide, 0 job classes |

## Where the ranks strained, and what was decided

**Every CI finding ranks *unenforced*.** That is structural, not a defect: the class is defined as
*decidable by a workflow step*, so its only failure mode is "no workflow step does it" — which is the
definition of *unenforced*. The same four ranks separate the Boundary class usefully (1 consumer,
2 promise, 3 unenforced, 1 style).

The obvious remedy was tested and rejected on the evidence. Splitting *unenforced* into **violated**
(the rule is broken now) and **unguarded** (a check is absent for a property that may well be fine)
partitions the CI class **16 to 1** — nearly every CI rule is "have this check", so not having it is
both at once. It adds a distinction the findings do not support.

What separates a uniform-rank class is the **cost** column, which already runs from `2 lines` to
`unknown`. Rank compares across the document; cost compares within a class.

**Two rows carry ⚠ security.** Unpinned actions and the `@main` reusable-workflow reference are both
live supply-chain exposure with no consumer-visible symptom and nothing false claimed. When the ranks
were settled, the first was named as the precedent to point at if a second of the same shape
appeared; the CI audit produced it. The flag, rather than a fifth rank, keeps the rank axis meaning
realized consequence — and left 22 findings unchanged.

## Audit complete

All six auditable classes are done — Boundary (34 clusters), CI (30), Prose (fleet) (38), Arch (11),
Larastan (6) and Pint (3): **122 of 226 rule clusters**. The remaining 104 are Prose (per-change),
ruled out of scope because an obligation on the next change has no fleet-wide fact behind it, and an
audit row for one cannot fail.

Every standard in the corpus now has a status above, clean ones included. **This document fixes
nothing** — each finding is a decision still to take, with the measurement in hand.
