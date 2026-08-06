# Liberu Roles and Permissions

> Contextual role assignments, module-owned permissions, policies, and cache invalidation.

[Software](https://liberusoftware.com) · [Hosting](https://liberuhosting.com) · [Services](https://liberuservices.com) · [Liberu Group](https://liberugroup.com)

[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/) [![Latest release](https://img.shields.io/github/v/release/liberusoftware/module-roles-permissions?sort=semver)](https://github.com/liberusoftware/module-roles-permissions/releases/latest) [![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE.md)

## Feature scope

Contextual role assignments, module-owned permissions, policies, and cache invalidation. This repository owns this capability as an independently versioned Composer package; hosts integrate it through its declared public boundaries rather than application-specific classes.

### Capabilities

- `foundation.authorization`

### Declared features

- Contextual role assignments
- Module-owned permissions
- Policies
- Cache invalidation

## Requirements and installation

| Dependency | Supported version |
|---|---|
| `php` | `^8.5` |
| `spatie/laravel-permission` | `^7.0` |
| `liberusoftware/module-manager` | `^1.0` |
| `liberusoftware/organizations-teams` | `^1.0` |

Install the released package from the project root:

```bash
composer require liberusoftware/roles-permissions
```

The trusted `liberusoftware/composer-installer` places it in `/modules/roles-permissions`. The installed directory remains tracked by the host repository, while Composer and its lock file remain the source of version truth. Installation does not imply runtime enablement or commercial entitlement.

## Architecture and integration

- Composer package: `liberusoftware/roles-permissions`
- Package type: `liberu-module`
- Installer name: `roles-permissions`
- Category: `foundation`
- Service provider: `Liberu\Foundation\RolesPermissions\RolesPermissionsServiceProvider`
- Enabled by default: `no`

### Public contracts

- `src/Contracts/PrivilegedActor.php`

### Commands

- No console command is provided.

### Events

- No package-owned event class is currently published.

### Persistence and permissions

- `database/migrations/2026_06_29_124225_create_permission_tables.php`
- `database/migrations/2026_08_01_062000_create_break_glass_grants_table.php`

Authorization remains the host application's responsibility unless a public authorizer contract is listed above. Consumers should grant only the permissions needed for the exported capabilities and must not couple to internal tables or classes.

## Testing

The package includes 2 test file(s). From a compatible host checkout, run:

```bash
vendor/bin/pest modules/roles-permissions/tests
```

Changes must preserve the manifest, service-provider integration, package boundaries, and PHP/Laravel compatibility declared above.

## Security

Do not report security vulnerabilities through public GitHub issues. Email `security@liberusoftware.com` with reproduction details and the affected version so the report can be handled privately.

## License

This module is open-source software available under the [MIT License](LICENSE.md). The linked licence text is authoritative.

## Feedback and contributing

Focused issues and tested pull requests are welcome in the [GitHub repository](https://github.com/liberusoftware/module-roles-permissions). Keep changes within this module's capability boundary, update tests and documentation, and record user-visible changes in `CHANGELOG.md`.

## Contributors

Thank you to everyone who helps improve Liberu. [View the contributors graph](https://github.com/liberusoftware/module-roles-permissions/graphs/contributors).
