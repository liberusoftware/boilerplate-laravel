# Liberu Identity

> Provider-neutral authentication policy, identifier normalization, and security events.

[Software](https://liberusoftware.com) · [Hosting](https://liberuhosting.com) · [Services](https://liberuservices.com) · [Liberu Group](https://liberugroup.com)

[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/) [![Latest release](https://img.shields.io/github/v/release/liberusoftware/module-identity?sort=semver)](https://github.com/liberusoftware/module-identity/releases/latest) [![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE.md)

## Feature scope

Provider-neutral authentication policy, identifier normalization, and security events. This repository owns this capability as an independently versioned Composer package; hosts integrate it through its declared public boundaries rather than application-specific classes.

### Capabilities

- `identity.authenticate`
- `identity.recover`

### Declared features

- Provider-neutral authentication policy
- Identifier normalization
- Security events

## Requirements and installation

| Dependency | Supported version |
|---|---|
| `php` | `^8.5` |
| `illuminate/support` | `^13.0` |
| `liberusoftware/module-manager` | `^1.0` |

Install the released package from the project root:

```bash
composer require liberusoftware/identity
```

The trusted `liberusoftware/composer-installer` places it in `/modules/identity`. The installed directory remains tracked by the host repository, while Composer and its lock file remain the source of version truth. Installation does not imply runtime enablement or commercial entitlement.

## Architecture and integration

- Composer package: `liberusoftware/identity`
- Package type: `liberu-module`
- Installer name: `identity`
- Category: `foundation`
- Service provider: `Liberu\Foundation\Identity\IdentityServiceProvider`
- Enabled by default: `no`

### Public contracts

- `src/Contracts/InvitationValidator.php`
- `src/Contracts/RegistrationPolicy.php`

### Commands

- No console command is provided.

### Events

- `src/Events/IdentityEvent.php`

### Persistence and permissions

- `database/migrations/2026_06_29_123953_create_passkeys_table.php`

Authorization remains the host application's responsibility unless a public authorizer contract is listed above. Consumers should grant only the permissions needed for the exported capabilities and must not couple to internal tables or classes.

## Testing

The package includes 2 test file(s). From a compatible host checkout, run:

```bash
vendor/bin/pest modules/identity/tests
```

Changes must preserve the manifest, service-provider integration, package boundaries, and PHP/Laravel compatibility declared above.

## Security

Do not report security vulnerabilities through public GitHub issues. Email `security@liberusoftware.com` with reproduction details and the affected version so the report can be handled privately.

## License

This module is open-source software available under the [MIT License](LICENSE.md). The linked licence text is authoritative.

## Feedback and contributing

Focused issues and tested pull requests are welcome in the [GitHub repository](https://github.com/liberusoftware/module-identity). Keep changes within this module's capability boundary, update tests and documentation, and record user-visible changes in `CHANGELOG.md`.

## Contributors

Thank you to everyone who helps improve Liberu. [View the contributors graph](https://github.com/liberusoftware/module-identity/graphs/contributors).
