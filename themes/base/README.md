# Liberu Base

> Shared semantic tokens, accessible layout, and progressive presentation primitives.

[Software](https://liberusoftware.com) · [Hosting](https://liberuhosting.com) · [Services](https://liberuservices.com) · [Liberu Group](https://liberugroup.com)

[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/) [![Latest release](https://img.shields.io/github/v/release/liberusoftware/theme-base?sort=semver)](https://github.com/liberusoftware/theme-base/releases/latest) [![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE.md)

## Visual scope

Shared semantic tokens, accessible layout, and progressive presentation primitives. It is a presentation-only package: domain behaviour stays in modules and the host application retains control of theme selection and fallback.

A maintained preview image is not yet published. Add an optimised, accessible preview under `.github/assets/` before claiming visual-regression coverage.

## Requirements and installation

| Dependency | Supported version |
|---|---|
| `php` | `^8.5` |
| `liberusoftware/theme-support` | `^1.0` |

Install from the host project root:

```bash
composer require liberusoftware/theme-base
```

The trusted `liberusoftware/composer-installer` places the package in `/themes/base`. Composer and its lock file remain the source of version truth, and the installed theme directory is committed by the host.

## Compatibility

- Type: `shared`
- Parent: `none`
- Optimised for: `liberusoftware/boilerplate-laravel:^1.0`
- Tested with: `liberusoftware/boilerplate-laravel:^1.0`
- Required capabilities: none
- Optional capabilities: foundation.localization
- Supported surfaces: application.shell, identity.auth, foundation.account

## Build and assets

Declared entry points:

- `resources/css/app.css`
- `resources/js/app.js`

Use the host's Vite build after installation:

```bash
npm install
npm run build
```

The theme supplies its own source assets and service provider. It does not bundle third-party fonts, images, icons, video, or templates; any such asset added later must include its licence and attribution.

## Accessibility and fallback

Preserve keyboard access, visible focus, semantic landmarks, readable contrast, reduced-motion preferences, zoom/reflow, and system-font fallbacks. Hosts must retain a working base/default theme when optional assets or capabilities are unavailable.

## Testing

Validate manifest parsing, provider registration, production asset compilation, supported host surfaces, responsive layouts, keyboard navigation, contrast, and fallback behaviour. Visual changes should include before/after evidence and should be checked at common viewport sizes.

## Security

Do not report vulnerabilities through public issues. Email `security@liberusoftware.com` with reproduction details and the affected version.

## License

This theme is open-source software under the [MIT License](LICENSE.md). The linked licence text is authoritative.

## Feedback and contributing

Focused issues and tested pull requests are welcome in the [GitHub repository](https://github.com/liberusoftware/theme-base). Keep visual changes accessible, document asset provenance, and update `CHANGELOG.md`.

## Contributors

Thank you to everyone who helps improve Liberu. [View the contributors graph](https://github.com/liberusoftware/theme-base/graphs/contributors).
