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

## How the audit is sliced

Not per standard. Every rule in the corpus was classified by the **mechanism that could catch it**,
and the classes cut across the files — `FILAMENT.md`, `LIVEWIRE.md` and `THEMES.md` hold most of one
class between them while `TESTING.md` holds a quarter of another. The full per-standard tables are in
[the rule classification](https://github.com/liberusoftware/boilerplate-laravel/blob/research/standards-classification/docs/research/standards-rule-classification.md).

| Class | Clusters | Audited |
| --- | --- | --- |
| **Boundary** — a package can check it about itself | 34 | **yes, below** |
| **CI** — decidable by a workflow step | 30 | not yet |
| **Arch** — whole-graph, needs every package in view | 11 | not yet |
| **Larastan** | 6 | not yet |
| **Pint** | 3 | not yet |
| **Prose (fleet)** — a standing property the audit can state | 38 | not yet |
| **Prose (per-change)** | 104 | **out of scope** — an obligation on the next change, with no fleet-wide fact behind it. An audit row for one cannot fail |

## Standard status

Measured against this tree on 2026-08-07: 40 `liberu-module` packages under `modules/`, 4
`liberu-theme` packages under `themes/`, and the host.

| Standard | Boundary class | Findings |
| --- | --- | --- |
| `API` | no Boundary rules | — |
| `ADOPTION` | no Boundary rules | — |
| `BLADE` | no Boundary rules | — |
| `CI` | no Boundary rules | — |
| `CLASSES` | no Boundary rules | — |
| `CONCERNS` | no Boundary rules | — |
| `CONTRACTS` | no Boundary rules | — |
| `CONTRIBUTING` | no Boundary rules | — |
| `CONTROLLERS` | no Boundary rules | — |
| `DATABASE` | audited | **1** |
| `DOCUMENTATION` | no Boundary rules | — |
| `DOMAIN-DRIVEN-DESIGN-PATTERNS` | no Boundary rules | — |
| `FILAMENT` | audited | **3** |
| `FRONTEND-TESTING` | vacuous | — no JavaScript presentation package exists |
| `GUIDELINES` | no Boundary rules | — |
| `JOBS` | vacuous | — the fleet ships **no job classes at all** |
| `LARAVEL` | audited | 0 |
| `LIVEWIRE` | audited | **1** |
| `MODELS` | audited | 0 — see the `DATABASE` finding |
| `OBJECT-ORIENTED-PROGRAMMING` | no Boundary rules | — |
| `PHP` | no Boundary rules | — |
| `PINT` | audited | 0 |
| `PSR` | audited | 0 |
| `QUEUES` | vacuous | — no job classes |
| `README` | index only | — |
| `SERVICES` | no Boundary rules | — |
| `TESTING` | audited | **1** |
| `THEMES` | audited | **1** |
| `TRANSLATIONS` | no Boundary rules | — |
| `VIEWS` | no Boundary rules | — |
| `FLUTTER` `INERTIA` `MOBILE` `NUXT` `REACT` `REACT-NATIVE` `VUE` | **inapplicable** | this stack has no React, Vue, Nuxt, Inertia, Flutter, React Native or mobile surface. Ruled out while charting, recorded here so the seven are not re-litigated |

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

## Not yet audited

CI (30 clusters), Arch (11), Larastan (6), Pint (3) and Prose (fleet) (38). Each is its own ticket
under the map. Until those land, a standard marked *no Boundary rules* above has been **classified,
not audited** — its rules live in a class this document has not reached.
