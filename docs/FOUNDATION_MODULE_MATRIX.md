# Foundation module implementation matrix

This matrix maps the canonical foundation catalog to independently owned Composer-style modules under `/modules`. Every package has a manifest, Composer metadata, provider, changelog, documentation, tests or host-level contract coverage, and explicit capability dependencies. The root `app/` contains composition only.

| Foundation capability | Package ownership | Implemented boundary |
|---|---|---|
| Application core | `liberusoftware/application` | environment validation, clocks, identifiers, readiness, maintenance and security headers |
| Module manager | `liberusoftware/module-manager` | deterministic discovery, dependency resolution, validation, cache, lifecycle events, status and diagnostics commands |
| Identity | `liberusoftware/identity-core`, `liberusoftware/identity-core-filament`, `liberusoftware/jetstream-bridge`, `liberusoftware/identity-socialstream` | identity policy/events and isolated Fortify, Jetstream and social provider adapters |
| Two-factor authentication | `liberusoftware/two-factor-authentication` | policy, recovery codes and trusted-device boundary |
| Sessions and devices | `liberusoftware/sessions-devices` | device/session projection and revocation contracts |
| Profiles | `liberusoftware/profiles` | profile data and preference update boundary |
| Organizations and teams | `liberusoftware/organizations-teams`, `liberusoftware/organizations-teams-filament` | lifecycle, membership, context, hardened invitations, ownership transfer, policies and optional Filament UI |
| Roles and permissions | `liberusoftware/roles-permissions`, `liberusoftware/roles-permissions-filament` | canonical permissions, contextual authorization, separation of duty, break-glass access and audit hooks |
| Localization | `liberusoftware/localization-core`, `liberusoftware/localization-core-livewire`, `liberusoftware/localization-mymemory` | locale resolution/context/formatting plus an optional disabled-by-default translation adapter |
| Currency context | `liberusoftware/currency-context` | precise money, ISO metadata, scoped preferences, formatting and immutable rate snapshots |
| Notifications | `liberusoftware/notifications` | delivery contracts, templates, preferences and inbox model |
| Files and media | `liberusoftware/files-media` | storage/access contracts and malware-scanning boundary |
| Search | `liberusoftware/search`, `liberusoftware/search-api` | driver-neutral indexing/search, reindex operations and an authenticated API |
| Audit | `liberusoftware/audit` | actor-aware append records and tamper-evident hash chain |
| Feature flags | `liberusoftware/feature-flags` | deterministic scoped evaluation and exposure boundary |
| API access | `liberusoftware/api-access` | token policy, service identities and idempotency handling |
| Webhooks | `liberusoftware/webhooks` | signed delivery, replay safety and retry boundary |
| Integrations | `liberusoftware/integrations` | credential-safe provider connection contracts |
| Analytics | `liberusoftware/analytics-core`, `liberusoftware/analytics-google`, `liberusoftware/analytics-meta` | consent-aware event core with isolated Google and Meta adapters |
| Import and export | `liberusoftware/import-export` | authorized, queued import/export contracts and progress state |
| Activity and comments | `liberusoftware/activity-comments` | activity stream and moderated comment boundary |
| Settings | `liberusoftware/settings`, `liberusoftware/settings-filament` | typed scoped precedence, encrypted secret separation and optional Filament UI |
| Scheduler and queues | `liberusoftware/scheduler-queues` | queue/schedule registration and health contracts |
| Observability | `liberusoftware/observability` | correlation, redaction, metrics, diagnostics, SLO boundary and optional vendor tooling |
| Developer experience | `liberusoftware/developer-experience` | foundation doctor and architecture diagnostics |
| Presentation composition | `liberusoftware/module-manager-filament`, `liberusoftware/sessions-devices-filament`, `liberusoftware/theme-support`, `liberusoftware/theme-support-livewire` | optional operational/account pages, theme discovery, inheritance and asset selection |

Product examples are no longer installed here. `blog-core`, `blog-filament`, `messaging`, `messaging-api`, `messaging-filament` and `search-demo` each own a repository of their own — see §3.4 of `docs/CONFORMANCE.md`. No foundation package ever depended on them, which is what made removing them a matter of dropping six Composer requires.

## Host boundary

Classes in `/app` are limited to host composition concerns: the `User` composition model, the two Filament panel providers, the manifest-driven Filament plugin composer, and `Support\ThemeColors`, which maps the site theme to a Filament palette — panel composition is a host concern, so it moved here when `foundation-filament` was dissolved. Root migrations own only application users and Laravel infrastructure. Capability migrations, routes, policies, commands, UI extensions, configuration, translations and views live with their owning module.

Run `php artisan module:validate` and `php artisan foundation:doctor` in CI and during deployment. Production may create a validated module cache with `php artisan module:cache`; changes to enabled modules, manifests, lockfiles or environment configuration require rebuilding that cache.
