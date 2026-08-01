# Liberu Module Manager

Discovers `liberu-module` packages from configured local install paths, validates their canonical `module.json`, resolves enabled dependencies deterministically, and registers enabled providers. Composer remains authoritative for installation; `MODULES_ENABLED` and `MODULES_DISABLED` are deployment state.

Module data is preserved when a module is disabled. Installation, upgrades, and removals are deployment operations and are never run during an HTTP request.

Use `module:list`, `module:status`, and `module:validate` for diagnostics. Deployment pipelines may run `module:cache`; set `MODULES_CACHE=true` only after that artifact exists, and use `module:clear` during recovery. Composer owns install/update/remove, while migrations and lifecycle hooks run as explicit locked deployment steps.
