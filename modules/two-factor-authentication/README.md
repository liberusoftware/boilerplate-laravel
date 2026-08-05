# Liberu Two-Factor Authentication

> TOTP enforcement, trusted-device policy, and single-use recovery primitives.

[Software](https://liberusoftware.com) · [Hosting](https://liberuhosting.com) · [Services](https://liberuservices.com) · [Liberu Group](https://liberugroup.com)

[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/) [![Latest release](https://img.shields.io/github/v/release/liberusoftware/module-two-factor-authentication?sort=semver)](https://github.com/liberusoftware/module-two-factor-authentication/releases/latest) [![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE.md)

## Feature scope

TOTP enforcement, trusted-device policy, and single-use recovery primitives. This repository owns this capability as an independently versioned Composer package; hosts integrate it through its declared public boundaries rather than application-specific classes.

### Capabilities

- `identity.two-factor`

### Declared features

- TOTP enforcement
- Trusted-device policy
- Single-use recovery primitives

## Requirements and installation

| Dependency | Supported version |
|---|---|
| `php` | `^8.5` |
| `illuminate/contracts` | `^13.0` |
| `illuminate/support` | `^13.0` |
| `liberusoftware/identity` | `^1.0` |

Install the released package from the project root:

```bash
composer require liberusoftware/two-factor-authentication
```

The trusted `liberusoftware/composer-installer` places it in `/modules/two-factor-authentication`. The installed directory remains tracked by the host repository, while Composer and its lock file remain the source of version truth. Installation does not imply runtime enablement or commercial entitlement.

## Architecture and integration

- Composer package: `liberusoftware/two-factor-authentication`
- Package type: `liberu-module`
- Installer name: `two-factor-authentication`
- Category: `foundation`
- Service provider: `Liberu\Foundation\TwoFactor\TwoFactorServiceProvider`
- Enabled by default: `no`

### Public contracts

- `src/Contracts/TwoFactorRecovery.php`

### Commands

- No console command is provided.

### Events

- No package-owned event class is currently published.

### Persistence and permissions

- `database/migrations/2026_06_29_123952_add_two_factor_columns_to_users_table.php`
- `database/migrations/2026_08_01_060000_create_two_factor_trusted_devices_table.php`

Authorization remains the host application's responsibility unless a public authorizer contract is listed above. Consumers should grant only the permissions needed for the exported capabilities and must not couple to internal tables or classes.

## Testing

The package includes 2 test file(s). From a compatible host checkout, run:

```bash
vendor/bin/pest modules/two-factor-authentication/tests
```

Changes must preserve the manifest, service-provider integration, package boundaries, and PHP/Laravel compatibility declared above.

## Security

Do not report security vulnerabilities through public GitHub issues. Email `security@liberusoftware.com` with reproduction details and the affected version so the report can be handled privately.

## License

This module is open-source software available under the [MIT License](LICENSE.md). The linked licence text is authoritative.

## Feedback and contributing

Focused issues and tested pull requests are welcome in the [GitHub repository](https://github.com/liberusoftware/module-two-factor-authentication). Keep changes within this module's capability boundary, update tests and documentation, and record user-visible changes in `CHANGELOG.md`.

## Contributors

Thank you to everyone who helps improve Liberu. [View the contributors graph](https://github.com/liberusoftware/module-two-factor-authentication/graphs/contributors).
