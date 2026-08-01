# Foundation compliance

The implementation follows three boundaries: the application composes, modules own behavior, and themes own presentation. Dependencies flow through declared capabilities and contracts rather than cross-package implementation classes.

Security-sensitive operations are deny-by-default and actor-aware. Organization context, authorization, invitations, ownership transfer, tokens, idempotency, files, webhooks, integrations and analytics expose explicit policy or adapter boundaries. Secrets are separated from ordinary settings, logs are redacted/correlated, and audit records form a tamper-evident chain.

Operational coverage includes readiness, maintenance, environment validation, module/theme deployment caches, queues, scheduler health, correlation IDs, metrics/SLO contracts and doctor commands. Provider-specific behavior is isolated behind optional packages. Product examples are not enabled as foundation dependencies.

Verification gates:

- `composer validate --no-check-publish` and locked Composer install reproducibility
- `php artisan module:validate`, `php artisan theme:validate`, and `php artisan foundation:doctor`
- fresh migrations and seeders
- Pest feature, unit and architecture suites
- Pint and Larastan/static analysis
- deterministic Vite build and asset budgets
- `git diff --check`, manifest JSON validation, and no reusable `App\\` references in modules

See [the module matrix](FOUNDATION_MODULE_MATRIX.md) for capability ownership and [the theme architecture](THEME_ARCHITECTURE.md) for presentation packaging, inheritance and release policy.
