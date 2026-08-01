# Foundation module implementation matrix

This matrix maps the canonical foundation catalog to independently owned Composer-style modules under `/modules`. Every package has a manifest, Composer metadata, provider, changelog, documentation, tests or host-level contract coverage, and explicit capability dependencies. The root `app/` contains composition only.

| Foundation capability | Package ownership | Implemented boundary |
|---|---|---|
| Application core | `liberu/application-core` | environment validation, clocks, identifiers, readiness, maintenance and security headers |
| Module manager | `liberu/module-manager` | deterministic discovery, dependency resolution, validation, cache, lifecycle events, status and diagnostics commands |
| Identity | `liberu/identity`, `liberu/jetstream-bridge`, `liberu/identity-socialstream` | identity policy/events and isolated Fortify, Jetstream and social provider adapters |
| Two-factor authentication | `liberu/two-factor-authentication` | policy, recovery codes and trusted-device boundary |
| Sessions and devices | `liberu/sessions-devices` | device/session projection and revocation contracts |
| Profiles | `liberu/profiles` | profile data and preference update boundary |
| Organizations and teams | `liberu/organizations-teams`, `liberu/organizations-teams-filament` | lifecycle, membership, context, hardened invitations, ownership transfer, policies and optional Filament UI |
| Roles and permissions | `liberu/roles-permissions` | canonical permissions, contextual authorization, separation of duty, break-glass access and audit hooks |
| Localization | `liberu/localization`, `liberu/localization-mymemory` | locale resolution/context/formatting plus an optional disabled-by-default translation adapter |
| Currency context | `liberu/currency-context` | precise money, ISO metadata, scoped preferences, formatting and immutable rate snapshots |
| Notifications | `liberu/notifications` | delivery contracts, templates, preferences and inbox model |
| Files and media | `liberu/files-media` | storage/access contracts and malware-scanning boundary |
| Search | `liberu/search`, `liberu/search-api`, `liberu/search-demo` | driver-neutral indexing/search, reindex operations, authenticated API and isolated demo projections |
| Audit | `liberu/audit` | actor-aware append records and tamper-evident hash chain |
| Feature flags | `liberu/feature-flags` | deterministic scoped evaluation and exposure boundary |
| API access | `liberu/api-access` | token policy, service identities and idempotency handling |
| Webhooks | `liberu/webhooks` | signed delivery, replay safety and retry boundary |
| Integrations | `liberu/integrations` | credential-safe provider connection contracts |
| Analytics | `liberu/analytics-core`, `liberu/analytics-google`, `liberu/analytics-meta` | consent-aware event core with isolated Google and Meta adapters |
| Import and export | `liberu/import-export` | authorized, queued import/export contracts and progress state |
| Activity and comments | `liberu/activity-comments` | activity stream and moderated comment boundary |
| Settings | `liberu/settings`, `liberu/settings-filament` | typed scoped precedence, encrypted secret separation and optional Filament UI |
| Scheduler and queues | `liberu/scheduler-queues` | queue/schedule registration and health contracts |
| Observability | `liberu/observability` | correlation, redaction, metrics, diagnostics, SLO boundary and optional vendor tooling |
| Developer experience | `liberu/developer-experience` | foundation doctor and architecture diagnostics |
| Presentation composition | `liberu/foundation-filament`, `liberu/theme-support` | optional operational/account pages, theme discovery, inheritance and asset selection |

Product examples remain isolated in `liberu/blog-core`, `liberu/blog-filament`, `liberu/messaging-core`, and `liberu/messaging-api`; no foundation package depends on them.

## Host boundary

The only classes in `/app` are the host `User` composition model and the two Filament panel composition providers. Root migrations own only application users and Laravel infrastructure. Capability migrations, routes, policies, commands, UI extensions, configuration, translations and views live with their owning module.

Run `php artisan module:validate` and `php artisan foundation:doctor` in CI and during deployment. Production may create a validated module cache with `php artisan module:cache`; changes to enabled modules, manifests, lockfiles or environment configuration require rebuilding that cache.
