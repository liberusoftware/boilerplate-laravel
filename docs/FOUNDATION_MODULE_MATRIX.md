# Foundation module implementation matrix

This matrix maps the canonical foundation catalog to independently owned Composer-style modules under `/modules`. Every package has a manifest, Composer metadata, provider, changelog, documentation, tests or host-level contract coverage, and explicit capability dependencies. The root `app/` contains composition only.

| Foundation capability | Package ownership | Implemented boundary |
|---|---|---|
| Application core | `liberusoftware/application-core` | environment validation, clocks, identifiers, readiness, maintenance and security headers |
| Module manager | `liberusoftware/module-manager` | deterministic discovery, dependency resolution, validation, cache, lifecycle events, status and diagnostics commands |
| Identity | `liberusoftware/identity`, `liberusoftware/jetstream-bridge`, `liberusoftware/identity-socialstream` | identity policy/events and isolated Fortify, Jetstream and social provider adapters |
| Two-factor authentication | `liberusoftware/two-factor-authentication` | policy, recovery codes and trusted-device boundary |
| Sessions and devices | `liberusoftware/sessions-devices` | device/session projection and revocation contracts |
| Profiles | `liberusoftware/profiles` | profile data and preference update boundary |
| Organizations and teams | `liberusoftware/organizations-teams`, `liberusoftware/organizations-teams-filament` | lifecycle, membership, context, hardened invitations, ownership transfer, policies and optional Filament UI |
| Roles and permissions | `liberusoftware/roles-permissions` | canonical permissions, contextual authorization, separation of duty, break-glass access and audit hooks |
| Localization | `liberusoftware/localization`, `liberusoftware/localization-mymemory` | locale resolution/context/formatting plus an optional disabled-by-default translation adapter |
| Currency context | `liberusoftware/currency-context` | precise money, ISO metadata, scoped preferences, formatting and immutable rate snapshots |
| Notifications | `liberusoftware/notifications` | delivery contracts, templates, preferences and inbox model |
| Files and media | `liberusoftware/files-media` | storage/access contracts and malware-scanning boundary |
| Search | `liberusoftware/search`, `liberusoftware/search-api`, `liberusoftware/search-demo` | driver-neutral indexing/search, reindex operations, authenticated API and isolated demo projections |
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
| Presentation composition | `liberusoftware/foundation-filament`, `liberusoftware/theme-support` | optional operational/account pages, theme discovery, inheritance and asset selection |

Product examples remain isolated in `liberusoftware/blog-core`, `liberusoftware/blog-filament`, `liberusoftware/messaging-core`, and `liberusoftware/messaging-api`; no foundation package depends on them.

## Host boundary

Classes in `/app` are limited to host composition concerns: the `User` composition model, the two Filament panel providers, and the manifest-driven Filament plugin composer. Root migrations own only application users and Laravel infrastructure. Capability migrations, routes, policies, commands, UI extensions, configuration, translations and views live with their owning module.

Run `php artisan module:validate` and `php artisan foundation:doctor` in CI and during deployment. Production may create a validated module cache with `php artisan module:cache`; changes to enabled modules, manifests, lockfiles or environment configuration require rebuilding that cache.
