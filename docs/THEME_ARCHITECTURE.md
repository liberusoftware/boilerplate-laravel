# Theme architecture

Themes are independent `liberu-theme` Composer packages installed in tracked `/themes/{name}` directories. They own presentation only: semantic design tokens, Blade composition and static assets. Business rules, authorization, validation, queries and mutations remain in modules.

## Packages and inheritance

| Theme | Type | Parent | Purpose |
|---|---|---|---|
| `liberusoftware/theme-base` | shared | none | accessible layout, semantic tokens, cascade layers and progressive JavaScript primitives |
| `liberusoftware/theme-default` | public | `base` | neutral light presentation |
| `liberusoftware/theme-dark` | public | `base` | dark presentation through token overrides |
| `liberusoftware/theme-clear-signal` | public | `base` | alternate high-clarity brand palette |

Each package includes `composer.json`, the canonical `theme.json`, provider, README, changelog and canonical `resources` tree. Child themes override only differing assets and inherit views/assets through a finite, validated parent chain.

## Installation and tracked sources

`liberusoftware/composer-installer` is a Composer 2 plugin that handles both `liberu-theme` and `liberu-module`, validates kebab-case installer names, rejects unsafe paths and collisions, and installs deterministically to `/themes` or `/modules`. Consuming applications explicitly require released theme packages, authorize the plugin, commit their lockfile, and commit the reproduced installed directories.

This repository is also the integration workspace for the bundled packages, so their authoritative package sources are already present at the installer destinations. Root path repositories require every bundled theme explicitly; Composer recognizes the tracked source at the deterministic installer path instead of overwriting it. Release consumers use the same package requirements against released package repositories.

## Runtime resolution

Selection precedence is tenant and surface, site and surface, configured surface, then safe default. User/session preference may select only a discovered compatible theme. Required capabilities must exist in the enabled module graph; invalid or incompatible selections emit a warning and use the configured fallback.

View lookup is child theme, parent chain, module default, then application fallback. Asset paths must be relative, traversal-free and declared in the manifest. Providers and inheritance are validated at startup or deployment cache creation. Useful commands are:

```bash
php artisan theme:validate
php artisan theme:cache
php artisan theme:clear
npm run build
```

## Presentation requirements

Themes consume semantic tokens for light, dark, contrast, state, focus, motion, spacing, typography, radius, elevation, borders, breakpoints and layering. The base layout supplies translated metadata defaults, logical-direction styling, landmarks and a skip link. CSS honors reduced motion, forced colors and bidirectional layout. JavaScript is progressive and CSP-safe; the switcher requires no inline script.

Visible strings must use translations. Images and media must include dimensions, appropriate alternative text, responsive delivery and recorded licensing. Third-party scripts require purpose, consent classification, version/integrity policy and documented failure behavior. Themes target WCAG 2.2 AA and must be verified with automated checks plus keyboard and representative screen-reader journeys.

Builds are deterministic and fail for missing manifest assets or unresolved imports. Generated `/public/build` output is deployment output; source assets remain in each package. Review compressed asset budgets, CSP headers, cache headers, privacy behavior and representative rendering before release.

## Release and upgrade

Release a theme from its independent repository, update the consuming Composer constraint and lockfile, reinstall, review the tracked `/themes` diff, validate, build and test. Resolve conflicts by selecting a package version and reinstalling—never by hand-merging installed output. Breaking manifest, token, slot or extension-point changes require a major version and migration notes.
