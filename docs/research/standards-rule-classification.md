# Standards rule classification

Resolution asset for [Classify every applicable standard's rules by how they can be
checked](https://github.com/liberusoftware/boilerplate-laravel/issues/644), a child of
[the code-conformance map](https://github.com/liberusoftware/boilerplate-laravel/issues/642).

Source read in full: `liberusoftware/documentation` `standards/`, 2026-08-07, at `main`.
2,978 lines across 36 files; 297 lines in the seven inapplicable frontend-framework standards,
leaving 29 files classified here.

## The taxonomy the ticket proposed, and why it needed two more classes

The ticket named five classes: **Pint**, **Larastan**, **Architecture test**, **Boundary suite**,
**Prose**. Classifying against them, two things did not fit.

**A large class of rules is mechanically checkable but by neither formatter nor analyser.**
`composer validate`, `composer dump-autoload --strict-psr`, a `theme.json` schema check, "never edit
a released migration", "pin actions to full-length commit SHAs", translation placeholder parity —
all decidable by a machine, none of them a Pint fixer or a PHPStan rule. Forcing them into *Prose*
would hide the cheapest findings in the whole audit. They are classed **CI**.

**"Prose" conflates two very different things.** Compare:

- *"Domain packages must not depend on Filament"* — a fact about the fleet as it stands today. The
  audit can state it, and the statement can be wrong.
- *"Test the permitted path and relevant denied paths"* — an obligation on whoever writes the next
  feature. There is no fleet-wide fact to state. An audit row saying "we should test denied paths"
  is unfalsifiable, and the map's own standing preference is that **a finding that cannot fail is
  not a finding**.

So *Prose* splits into **Prose (fleet)** and **Prose (per-change)**. This matters more than it
sounds: **Prose (per-change) is not auditable at all** and should be ruled out of the audit's scope
rather than written up. It is the single largest class by line count.

### Final classes

| Class | Meaning | Auditable? |
| --- | --- | --- |
| **Pint** | a formatting rule; a fixer exists or can be configured | yes — and continuously gated after #643 |
| **Larastan** | a type or correctness rule a level or extension catches | yes — continuously gated after #643 |
| **Arch** | whole-graph; checkable only with every package in view (host `tests/Architecture`) | yes |
| **Boundary** | a package can check it about *itself* → `package-testbench` | yes |
| **CI** | mechanically decidable by a workflow step that is none of the above | yes |
| **Prose (fleet)** | judgement, but about a standing property of the fleet the audit can state | yes |
| **Prose (per-change)** | judgement about a specific change; no fleet-wide fact exists | **no — belongs in review, not the audit** |

## Distribution

Rule clusters, not clauses — a cluster is one reviewable assertion.

| Class | Clusters | Share |
| --- | --- | --- |
| Prose (per-change) | 104 | 46% |
| Prose (fleet) | 38 | 17% |
| Boundary | 34 | 15% |
| CI | 30 | 13% |
| Arch | 11 | 5% |
| Larastan | 6 | 3% |
| Pint | 3 | 1% |
| **Total** | **226** | |

**Consequence for the map:** the audit's real surface is ~122 clusters (Boundary + CI + Arch +
Prose (fleet)), not 226. Just under half the standards' text states per-change obligations that no
audit can measure. The four tool classes together are only 22% — but they are the ones that stop
needing an audit once turned on.

---

## PHP.md

| Rule | Class | Note |
| --- | --- | --- |
| `declare(strict_types=1);` in new executable files | Pint | `declare_strict_types` fixer; **not in the `laravel` preset** — the shared `pint.json` must add it |
| Typed properties, parameters, return values | Larastan | `missingType.*`, level 6 |
| Enums/value objects for constrained values | Prose (per-change) | |
| Immutable DTOs, `readonly` where mutation is not required | Prose (per-change) | |
| Exceptions, never sentinel values hiding authorization or data loss | Prose (per-change) | |
| No dynamic properties | Larastan | `property.notFound`; also a PHP 8.2 deprecation |
| No variable variables, `eval`, unsafe deserialization, broad catch | CI | grep-shaped; no stock PHPStan rule |
| `password_*`, `random_bytes`, framework crypto over custom primitives | CI | grep-shaped |
| Secrets out of source, logs, URLs, serialized props, tests, exception messages | CI | secret scanning; **no such job exists** |
| Composer autoloading; never scan directories to invent an autoloader | Arch | host rule exists |
| Format with Pint; analyse at the supported static-analysis level | Pint, Larastan | gated by [#643](https://github.com/liberusoftware/boilerplate-laravel/issues/643) |

## PSR.md

| Rule | Class | Note |
| --- | --- | --- |
| PSR-12 mechanics: LF, UTF-8 no BOM, final newline, no closing tag, 4 spaces, no trailing whitespace, header/import order, visibility, short types, `PascalCase`/`camelCase`/`UPPER_SNAKE_CASE` | Pint | the `laravel` preset is a PSR-12 superset — already passing in 44 of 44 |
| 120-character soft line limit | Prose (fleet) | "soft"; Pint has no line-length fixer |
| One class/interface/trait/enum per file | Boundary | no fixer; trivially assertable over a package's own `src/` |
| PSR-4 autoloading, stable namespaces, no runtime filesystem scanning | Arch + CI | host rule exists; `composer dump-autoload --strict-psr` is the CI half and **is not run** |
| Type against PSR-3/6/7/11/14/15/16/17/18/20 where the boundary is genuinely replaceable | Prose (per-change) | the standard explicitly forbids adding them for decoration |
| Do not adopt PSR-0/PSR-2 | Boundary | assertable; vacuously true today |
| Document which PSR interfaces are implemented, consumed, or not applicable | CI | a README content check; **absent** |
| CI runs formatter, static analysis, autoload validation, architecture checks, tests | CI | three of five after #643 |

## PINT.md

| Rule | Class | Note |
| --- | --- | --- |
| Pin Pint in Composer dev dependencies; lock file is the version authority | Boundary | satisfied transitively through `package-testbench` after #643 |
| `pint.json` at the repository root | — | **deliberate deviation**, decided in [#643](https://github.com/liberusoftware/boilerplate-laravel/issues/643); the audit records the reason rather than re-litigating |
| Strictest supported preset; risky rules only when reviewed | Prose (fleet) | "strictest" is not a defined value |
| Format owned PHP: source, tests, migrations, seeders, factories, config, commands, providers, package code | CI | a path-scope question; the current invocation covers whole package roots |
| Do not format vendor, generated, build artifacts; exclusions narrow and documented | CI | |
| Run before commit and in CI; a PR fails when formatting is required | CI | gated after #643 |
| Keep formatting-only changes separate from behavioural changes | Prose (per-change) | |

## LARAVEL.md

| Rule | Class | Note |
| --- | --- | --- |
| Domain rules in modules; controllers/Livewire/Filament/Inertia only orchestrate | Arch + Boundary | host domain→presentation rules; testbench "no Filament in non-presentation modules" |
| Route model binding, form requests, policies, middleware, providers used deliberately | Prose (per-change) | |
| Resolve team/tenant context before protected queries; route guards are not authorization | Prose (per-change) | |
| Eloquent for persistence ownership, query objects for complex reads, transactions for local invariants | Prose (per-change) | |
| Events after commit; retryable jobs idempotent; queues for over-budget work | Prose (per-change) | |
| Config in `config/`, environment values in secrets, composition in the root application | Arch | host "config stays list-free" rule is the existing half |

## CLASSES.md · OBJECT-ORIENTED-PROGRAMMING.md · CONCERNS.md · DOMAIN-DRIVEN-DESIGN-PATTERNS.md

Grouped: four standards, 59 lines, and almost entirely design judgement.

| Rule | Class | Note |
| --- | --- | --- |
| Constructor injection; no service-locator calls; no static mutable state | Larastan | custom rule banning `app()`/`resolve()`/`Facade` in domain namespaces is feasible |
| Explicit visibility and types | Larastan | already covered by the level |
| One cohesive responsibility; cheap constructors; small immutable value objects | Prose (per-change) | |
| Invalid state difficult to create; validate at trust boundaries | Prose (per-change) | |
| Test public behaviour, not private implementation | Prose (per-change) | |
| Traits narrow, namespaced, documented, safe on multiple owners | Prose (per-change) | |
| Traits must not hide authorization, queries, transactions, external calls, boot hooks | Prose (fleet) | assertable by reading the fleet's traits — a bounded, finite set |
| Two real owners required before adding a trait | Prose (fleet) | assertable: a trait with one owner is a finding |
| Keep inheritance shallow and intentional | Prose (fleet) | depth is measurable; "intentional" is not |
| Interfaces only where consumers need substitution | Prose (fleet) | an interface with one implementation and one consumer is assertable |
| Bounded contexts, aggregates, value objects, domain services, repositories, read models | Prose (per-change) | |
| Publish domain events after committed changes; outbox/inbox/sagas | Prose (per-change) | |
| Never expose private persistence as a cross-module contract | Arch | host cross-package namespace-dependency rule |

## SERVICES.md · CONTROLLERS.md · MODELS.md

| Rule | Class | Note |
| --- | --- | --- |
| One responsibility per service; verb-based action names | Prose (fleet) | naming is assertable across the fleet |
| Inject contracts; framework adapters at the boundary | Prose (per-change) | |
| Authorize before protected reads/mutations; establish tenant context; explicit transaction scope | Prose (per-change) | |
| Return typed results/read models; documented domain errors | Prose (per-change) | |
| External calls behind provider-neutral adapters with timeouts, retries, reconciliation | Prose (fleet) | the fleet's adapter packages are a finite set |
| One controller method per route/use case | Prose (fleet) | assertable — the host has two routes |
| No database queries, SDK calls, or cross-module orchestration in controllers | Larastan | custom rule; feasible over `Http/Controllers` namespaces |
| A module owns its tables, migrations, casts, indexes, retention, export, deletion | Boundary | a package can assert its migrations only touch its own prefix |
| Guarded/validated assignment, explicit casts, relationships, scopes, constraints | Prose (per-change) | |
| No hidden queries in accessors, serialization, views, jobs, authorization | Prose (per-change) | |
| Do not expose private models or tables as cross-module extension points | Arch | |
| Prevent N+1 intentionally | Prose (per-change) | |

## JOBS.md · QUEUES.md

| Rule | Class | Note |
| --- | --- | --- |
| Pass stable identifiers and immutable values, never live models or request/session state | Larastan | custom rule over job constructor parameter types is feasible |
| Establish tenant context explicitly; fail closed when absent | Prose (per-change) | |
| Handlers safe to retry; idempotency keys, unique jobs, deduplication | Prose (per-change) | |
| Explicit connection, queue, timeout, `tries`, `backoff`, `maxExceptions`, dead-letter | Boundary | a package can assert its own job classes declare them |
| Dispatch after commit when the job depends on committed state | Prose (per-change) | |
| Redact secrets and personal data from payloads and logs | Prose (per-change) | |
| Monitor age, throughput, failure rate, retries, dead letters, saturation | Prose (fleet) | an operational fact the audit can state — Horizon is installed |
| Document replay and discard runbooks | CI | a docs-presence check |

## DATABASE.md

| Rule | Class | Note |
| --- | --- | --- |
| A module owns its tables, indexes, foreign keys, constraints, migrations, upgrade path | Boundary | |
| **Never edit a released migration** | CI | mechanically decidable — diff `database/migrations/` against the last release tag. Cheap, and nothing checks it |
| Migrations deterministic, reversible where practical, rolling-deploy safe | Prose (per-change) | |
| Separate destructive from additive; backfill in resumable jobs | Prose (per-change) | |
| Seeders explicit, repeatable, environment-aware; baseline separate from demo | Prose (fleet) | the fleet's seeders are a finite set |
| Never seed credentials, personal data, secrets, nondeterministic records | CI | grep-shaped |
| Stable keys and upserts so reruns do not duplicate | Prose (per-change) | |
| Factories express valid states; must not bypass actions, policies, tenant context | Prose (per-change) | |
| Constraints for invariants, indexes for observed access paths, transactions for local consistency | Prose (per-change) | |
| Prevent N+1 and hidden access in views, serializers, policies, jobs, accessors | Prose (per-change) | |

## BLADE.md · VIEWS.md

| Rule | Class | Note |
| --- | --- | --- |
| Escape by default; sanitize approved rich content through a shared service | CI | `{!! !!}` occurrences are enumerable and each is a review point |
| Components small, explicit, independent of ambient database queries | Prose (per-change) | |
| Use layouts, slots, components, translation strings, locale-aware formatting, semantic HTML | Prose (per-change) | |
| `@csrf`, safe URL generation, authorization directives for presentation only | CI | `@csrf` presence in forms is grep-shaped |
| Localize all user-facing copy | CI | a hardcoded-string check; **absent** |
| Keep Livewire behaviour in Livewire, domain mutations in authorized actions | Arch | |
| Cover loading, empty, error, unauthorized, offline, validation, success states | Prose (per-change) | |
| Keep layout regions, slots, test IDs, landmarks, focus behaviour, extension points stable | Prose (per-change) | a versioning obligation |
| Pass typed view models; no ambient database state | Prose (per-change) | |

## FILAMENT.md

The densest source of mechanical rules in the whole set.

| Rule | Class | Note |
| --- | --- | --- |
| Name starts `module-`/`theme-`, ends `-filament`, kebab-case between | Boundary | partially covered by the existing module boundary suite |
| Plugin ID equals the installer name | Boundary | |
| Composer type `liberu-module`/`liberu-theme`; installs to `/modules`/`/themes` | Boundary | exists |
| Manifest declares its matching domain module as a required dependency and no second one | Arch + Boundary | **one-to-one ownership is not currently checked** |
| Domain module must not depend on Filament | Boundary | exists |
| A reusable package supplies a plugin, not a `PanelProvider` | Boundary | assertable: no `PanelProvider` subclass in package `src/` |
| Plugin class final-by-default, static `make()`, stable ID, `register()`/`boot()` split | Boundary | |
| Discovery bounded to the package's own namespace and path; never scans app, `/modules`, `/themes`, `/vendor` | Boundary | grep-shaped and decisive |
| Slugs, routes, widget IDs, Livewire aliases, navigation groups, plugin IDs collision-checked | Arch | host `ModulePlugins` already rejects duplicate plugin IDs |
| Declared panel matrix implemented; **CI fails when a declared panel requirement has no mapped implementation** | CI | the standard demands a CI gate by name. **Nothing declares a panel matrix today** |
| Resources belong to `module-*-filament`, never `theme-*-filament` | Boundary | |
| Discovery results cacheable and reproducible; no runtime filesystem scans in production | CI | |
| README documents 18 named items including the panel matrix and discovery namespaces | CI | a docs-content check; **absent** |
| Resources delegate to domain actions; queries enforce tenant scope | Prose (per-change) | |
| Navigation visibility is not authorization | Prose (per-change) | |
| Widgets bounded, cache/freshness stated, no cross-module private storage | Prose (per-change) | |
| Destructive actions confirmed and scoped; bulk operations bounded, queued, idempotent, auditable | Prose (per-change) | |
| Themes may style widgets but never own functional ones | Boundary | |
| Semantic versioning; breaking changes to stable surfaces need a major release | Prose (fleet) | tag history is evidence |

## LIVEWIRE.md

| Rule | Class | Note |
| --- | --- | --- |
| Name starts `module-`/`theme-`, ends `-livewire` | Boundary | |
| One stable component namespace per package; alias omits `-livewire`, keeps the prefix | Boundary | |
| Aliases, route names, public props, events, slots, test IDs stable and collision-checked | Arch | |
| Class-based components by default; SFC/MFC only with a documented reason | Boundary | |
| **SFC/MFC filenames must not use the bolt emoji** (breaks Composer packaging) | CI | one of the cheapest checks in the corpus |
| Registration bounded to the package namespace; no recursive scanning | Boundary | |
| A package must not silently claim `/`, `/dashboard`, `/admin`, or another broad path | Arch | host can enumerate registered routes |
| Public properties minimal, typed, serializable, secret-free; locked where client must not change | Larastan (partial) + Prose (per-change) | typing is analysable; sensitivity is not |
| Protected/private properties are not persisted between requests | Prose (fleet) | assertable by reading the fleet's components |
| Every action validates and authorizes server-side | Prose (per-change) | |
| Public helpers not intended as actions are protected from arbitrary invocation | Boundary | assertable over a component's public method surface |
| Event names package-qualified, e.g. `module-cms.post-saved` | Boundary | grep-shaped |
| **Islands must not sit directly inside loop or conditional control structures** | CI | a Blade lint; mechanical |
| Polling opt-in, visibility-aware, stopped when unnecessary | Prose (per-change) | |
| `wire:navigate` only on compatible same-origin journeys; listeners cleaned up | Prose (per-change) | |
| Redirect destinations validated; no open redirects from public properties | Larastan | custom rule feasible |
| Rendering, accessibility, RTL, translation, focus, reduced motion | Prose (per-change) | |
| Semantic versioning; breaking alias/event changes need a major release | Prose (fleet) | |

## THEMES.md

The one standard whose mechanical rules are already largely enforced — and the clearest source of
gaps.

| Rule | Class | Note |
| --- | --- | --- |
| `theme.json` present; schema, unique name, paths, capabilities, inheritance cycles validated | Boundary | **exists** — `ThemeBoundaryTest` in the testbench |
| Declared provider is a real service provider | Boundary | exists |
| Every declared parent resolves; cycles rejected | Boundary + Arch | exists both sides |
| Every declared asset path exists | Boundary | exists |
| A child without `layouts/app.blade.php` falls back to its parent | Boundary | exists — render contract |
| Type `liberu-theme`; installs to `/themes/{theme-name}` | Boundary | exists |
| Vite derives entry points from manifests; no maintained literal list | Arch | host `vite.config.js` complies |
| Required workflows: `install.yml`, `tests.yml`, **`visual.yml`**, `compatibility.yml` | CI | **themes ship three of four — no `visual.yml` anywhere** |
| Composer scripts `test`, `test:feature`, `test:coverage`, `test:parallel` | Boundary | all four themes have `"test": "pest"` and **none of the other three** |
| `test:coverage` enforces `--min=100` with Clover and HTML output | CI | **absent**; the ratchet is far below 100 |
| `require-dev` is testbench + Pest 5 (+ Pest plugins); no separate `phpunit/phpunit` | Boundary | themes comply |
| README documents ~25 named items | CI | a docs-content check |
| Design tokens cover light, dark, high-contrast, error, warning, success, disabled, focus | Prose (fleet) | the four themes are a finite, readable set |
| Cascade-layer order, scoped component styles, performance budgets | Prose (fleet) | |
| WCAG 2.2 AA; keyboard and screen-reader checks each release | Prose (per-change) | |
| Images, logos, icons, video: alt text, responsive variants, licensing, provenance | Prose (per-change) | |
| Consent before analytics/advertising/chat; CSP; SRI | Prose (fleet) | two analytics modules exist and ship disabled |

## TESTING.md

| Rule | Class | Note |
| --- | --- | --- |
| Pest 5 under `require-dev` at `^5.0`; **do not require `phpunit/phpunit` separately** | Boundary | packages comply; **the host requires `phpunit/phpunit ^13.1.8` explicitly** |
| `phpunit.xml`, Pest config, bootstrap, Composer scripts versioned | Boundary | complies |
| Never depend on developer-global binaries | Boundary | complies |
| Standard suite directory layout | Prose (fleet) | the fleet uses a deliberate subset — the deviation is already documented |
| Composer scripts `test`, `test:unit`, `test:feature`, `test:coverage`, `test:parallel` | Boundary | 44 of 44 packages and the host declare `test` alone — **four of the five are absent everywhere** |
| Coverage scope is meaningful owned PHP, with a named exclusion list | CI | package `<source>` is `src` — compliant |
| 100% target; a threshold below 100% is a migration state; raise deliberately | CI | the ratchet exists and is honest about being a migration state |
| Release must not publish below 100% of release scope | CI | **not enforced; the fleet's median is 20%** |
| Machine-readable + HTML reports as protected artifacts, not committed | CI | **not produced by the reusable workflow** |
| README badge reflects the default branch and links its report | CI | READMEs carry PHP, release and licence badges; **no coverage badge and no CI-status badge anywhere** |
| CI fails on unexpected `/modules` and `/themes` changes | CI | **exists in the host** — the §6.2 zero-diff gate |
| Prefer changed-code and critical-package gates alongside the threshold | Prose (fleet) | |
| Flaky test quarantine requires owner, issue, risk, expiry | Prose (per-change) | |
| Everything in §§7–12, 17–19 — what to test and how | Prose (per-change) | ~60 clusters, the largest single block in the corpus |

## CI.md

| Rule | Class | Note |
| --- | --- | --- |
| `tests.yml` runs formatting, static analysis, architecture checks, tests, API checks, security checks, coverage | CI | four of seven after #643 |
| `install.yml` verifies PHP/Node versions, lock files, install, bootstrap | CI | exists |
| `docker.yml` builds, scans, publishes | CI | host only |
| `release.yml` validates a protected tag, approval, deploy, verify/rollback | CI | host only |
| Required gates include `composer validate` and locked dependency installation | CI | host does both; **no package runs `composer validate`** |
| Dependency, secret, container security scans | CI | host runs `composer audit --locked`; **packages run none** |
| **Pin third-party actions to full-length commit SHAs** | CI | **violated everywhere** — `actions/checkout@v5`, `shivammathur/setup-php@v2`, `actions/cache@v4` in host and reusable workflows alike |
| Least-privilege `permissions:`, starting `contents: read` | CI | reusable workflows comply; **host `docker.yml`/`release.yml` unverified** |
| `id-token: write` only where OIDC deployment needs it | CI | |
| Tags must not be moved or force-updated | Prose (fleet) | `fleet tag` refuses a re-tag — a mechanism already exists |
| Production release gated on 100% release-scope coverage plus security checks | CI | **not enforced** |
| Never expose production secrets to fork PRs | CI | |
| The release workflow tests the exact commit it deploys | Prose (fleet) | |

## TRANSLATIONS.md

The standard names its own CI checks, which is unusual and makes the gap unambiguous.

| Rule | Class | Note |
| --- | --- | --- |
| Namespaced stable keys such as `modules.billing.invoices.status.paid`; never English copy as a key | CI | key-shape check |
| **CI checks key uniqueness, placeholder parity, missing keys, invalid plural forms, encoding, stale keys, locale coverage** | CI | seven named checks. **None exist** |
| User-visible strings out of domain classes and controllers | CI | grep-shaped |
| ICU/Laravel pluralization with named variables; never concatenate translated fragments | Prose (per-change) | |
| Dates, numbers, currencies, timezones through the shared locale context | Prose (per-change) | |
| Locale resolved from trusted config and validated against the enabled set; deterministic fallback | Prose (fleet) | `SetLocale` is a single readable implementation |
| No untranslated developer errors, secrets, identifiers, or personal data in fallbacks | Prose (per-change) | |
| Bidirectional layouts with logical CSS properties; translated assets; language-appropriate fonts | Prose (fleet) | four themes, finite |
| A locale change preserves authorization, tenant context, form state, audit semantics | Prose (fleet) | assertable — and the known session-shadowing behaviour is a candidate finding |
| Pseudo-localization and representative RTL browser tests | CI | **absent** |
| Adding or changing a public key is a documented contract change | Prose (per-change) | |

## API.md · DOCUMENTATION.md · CONTRIBUTING.md · GUIDELINES.md · ADOPTION.md · FRONTEND-TESTING.md

| Rule | Class | Note |
| --- | --- | --- |
| Version public contracts; document auth, permissions, tenancy, limits, errors, idempotency | Prose (per-change) | |
| RFC 9457 error bodies; purpose-built resources | Prose (fleet) | the fleet exposes one `api-access` module — a finite surface |
| OpenAPI schema linting, example validation, drift and breaking-change detection | CI | **no OpenAPI document exists** |
| Markdown lint, internal/external link check, heading and code-fence validation | CI | **absent** |
| One H1, sentence-case headings, descriptive links, fenced code labels, relative internal links | CI | markdownlint-shaped |
| **must**/**should**/**may** used precisely | Prose (per-change) | |
| A new document is added to the nearest index; inbound links updated on rename | CI | link check covers it |
| Do not claim support, coverage, security, or deployment capability without current evidence | Prose (fleet) | directly relevant — this is the failure mode the predecessor map kept finding |
| Documentation structure `docs/{adr,runbooks,upgrades,guides,reference}` | Prose (fleet) | **none of the five exists.** `CLAUDE.md` names `CONTEXT.md` and `docs/adr/` as this repository's domain-doc layout; neither is present |
| Imperative commit subjects; coherent commits; no mixed generated output | Prose (per-change) | |
| Progressive delivery: same domain model, contracts, policies across profiles | Prose (fleet) | |
| Capability-based optional infrastructure bound at composition time | Prose (fleet) | the module system is exactly this — a compliance point, not a gap |
| Frontend testing: unit, component, contract, browser, accessibility, production build | — | **vacuous** — no JavaScript presentation package exists. The audit records it as inapplicable-in-practice alongside the seven ruled out while charting |

---

## What this changes for the map

**1. The per-standard audits can now be sliced — but not per standard.** Slicing by standard was
always wrong; the classes cut across them. `FILAMENT.md`, `LIVEWIRE.md` and `THEMES.md` between them
hold most of the Boundary rules, while `TESTING.md` alone holds a quarter of the per-change prose.
The natural units are **the seven classes**, not the 29 files.

**2. Four of the seven classes are one ticket each, not many.** Pint (3), Larastan (6) and Arch (11)
are small enough to audit in a single pass, and after #643 the first two report themselves. Boundary
(34) and CI (30) are the substantial mechanical work.

**3. Prose (per-change) — 46% of the corpus — should be ruled out of the audit's scope.** Not out of
the standards: out of the *audit*. There is no fleet-wide fact to state about "test the denied path",
and a document full of such rows would be exactly the unfalsifiable coverage the map warns against.
Where it belongs is a review checklist, which is a different artifact with a different reader.

**4. Ten candidates are already visible** without auditing anything — each verified against this
tree on 2026-08-07, not inferred:

| Candidate | Evidence |
| --- | --- |
| GitHub Actions unpinned to full-length SHAs | `actions/checkout@v5`, `shivammathur/setup-php@v2`, `actions/cache@v4` — host and all three reusable workflows |
| No `visual.yml` in any theme | all four ship `install.yml`, `tests.yml`, `compatibility.yml` |
| Seven named translation checks absent | no workflow references key uniqueness, placeholder parity, plural forms, stale keys or locale coverage |
| Four of five Composer script aliases missing | 44 of 44 packages and the host declare `"test": "pest"` and nothing else |
| Host requires `phpunit/phpunit` separately | `require-dev` carries `^13.1.8`; TESTING.md §3 prohibits it when Pest supplies the runtime |
| No coverage badge or CI-status badge | READMEs carry PHP, release and licence badges only |
| No coverage artifact upload | the reusable workflow produces no Clover or HTML report |
| No `composer validate` in any package | the reusable workflow runs `composer update` then Pest |
| No OpenAPI document | nothing at any conventional path, while `API.md` requires generated-contract checks |
| No panel matrix in any `-filament` manifest | zero of the manifests declare panels, and FILAMENT.md §3.1 asks CI to *fail* on an unmapped declared panel |

A separate one worth naming because it contradicts this repository's own instructions rather than a
standard: `CLAUDE.md` declares the domain-doc layout to be a root `CONTEXT.md` plus `docs/adr/`.
**Neither exists.**

These are candidates, not findings: each needs the rank
[Define what a finding is, and how findings rank](https://github.com/liberusoftware/boilerplate-laravel/issues/645)
will supply.
