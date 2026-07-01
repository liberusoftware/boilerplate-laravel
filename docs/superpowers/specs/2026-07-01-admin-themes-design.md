# Admin-Controlled Site Theme — Design Spec

**Date:** 2026-07-01
**Status:** Approved (brainstorming)

## Problem

The app already has a full theming stack (`ThemeManager`, `ThemeServiceProvider`,
per-user `ThemeSwitcher`, `config/theme.php`, `themes/default` + `themes/dark`,
`users.theme_preference`, tests). What's missing is an **admin-panel control that
sets a site-wide theme**. Today the active theme resolves only from
`user preference → session → config('theme.default')`; there is no runtime,
admin-editable site default.

## Goal

Let an admin choose the site-wide theme from the Filament admin panel. Ship with
**one default theme** and **no visual change** (default theme reproduces the
current Amber-primary look). Adding a theme later = drop a folder + `theme.json`.

## Scope Decisions (from brainstorming)

1. **Admin scope:** Admin sets the site **default**; individual users can still
   override via the existing per-user switcher.
   Frontend resolution becomes: `user pref → session → site theme → config fallback`.
2. **Surfaces:** Theme restyles **frontend Blade** *and* **Filament panels**.
3. **Filament depth:** Drive Filament panel **color palette** from `theme.json`
   now; reserve a documented hook (`theme.json.filament_css`) for future compiled
   per-theme CSS. No compiled Filament CSS per theme in this iteration.
4. **Panel boundary:** Filament panels follow the **site-wide** theme only (not
   per-user) — panel config resolves once at request boot; per-user panel theming
   is deferred.

## Architecture

```
Admin (ManageSiteSettings) → SiteSettings.active_theme ─┬─ Frontend: ThemeServiceProvider
                                                         │   resolution (user → session → SITE → config)
                                                         └─ Filament: AdminPanelProvider + AppPanelProvider
                                                             ->colors( ThemeManager::getFilamentColors(site) )
```

Single new concept: `SiteSettings.active_theme`. Everything else reads it.

## Components

| Unit | Change | Responsibility |
|------|--------|----------------|
| `App\Settings\SiteSettings` | add `public string $active_theme` | Site-wide theme, one source of truth |
| `database/settings/*_add_active_theme_to_site_settings.php` | `migrator->add('site.active_theme','default')` | Non-null default so app/tests boot |
| `App\Filament\Pages\ManageSiteSettings` | add `Select` (options ← `ThemeManager::getThemes()`) in a new "Appearance" section | Admin control |
| `App\Services\ThemeManager` | + `getSiteTheme(): string`, + `getFilamentColors(?string $theme): array` | Read site theme (DB, safe fallback); map `theme.json.colors` → Filament palette |
| `App\Providers\ThemeServiceProvider::determineActiveTheme()` | insert `getSiteTheme()` between session and config; validate theme exists | Frontend honors admin choice |
| `App\Providers\Filament\AdminPanelProvider` + `AppPanelProvider` | `->colors($themeManager->getFilamentColors($themeManager->getSiteTheme()))` | Panels restyle per site theme |
| `themes/default/theme.json` | set `colors.primary` = `amber` (+ `secondary`); add note re `filament_css` hook | Preserve current look; document extension point |
| `phpunit.xml` | add fixed test `APP_KEY` | Unblock Filament page tests (pre-existing `MissingAppKeyException`) |

## Interfaces

```php
// ThemeManager
public function getSiteTheme(): string;
// Reads SiteSettings.active_theme; on any failure (DB missing, setting absent,
// unknown theme) returns config('theme.default','default'). Never throws.

public function getFilamentColors(?string $theme = null): array;
// Returns e.g. ['primary' => Color::Amber]. Reads theme.json 'colors.primary'
// (a lowercase Tailwind name), maps to a Filament Color const. Unknown/missing
// color → ['primary' => Color::Amber]. Shape is a valid argument to Filament
// panel ->colors().
```

## Frontend resolution (final order)

`user.theme_preference (if non-empty) → session('theme_preference') → ThemeManager::getSiteTheme() → config('theme.default')`.
Any resolved value that isn't a real theme falls through to the next source; the
final config value is guaranteed to exist (`themes/default`).

## Data flow

- **Frontend:** `ThemeServiceProvider::boot()` calls `setTheme(determineActiveTheme())`
  per request; unchanged except the new site layer.
- **Filament panels:** each provider calls
  `->colors($tm->getFilamentColors($tm->getSiteTheme()))` at panel registration.
  DB read is wrapped safely so console/migrations don't break.

## Color contract (`theme.json`)

```json
"colors": { "primary": "amber", "secondary": "slate" }
```
`ThemeManager::getFilamentColors()` maps `primary` via an explicit whitelist to a
`Filament\Support\Colors\Color` const. Future compiled Filament CSS would be
declared as an optional `"filament_css": "themes/<name>/filament.css"` key and
wired via `->viteTheme()` — **not** built in this iteration.

## Testing (TDD)

1. After migrate, `app(SiteSettings::class)->active_theme === 'default'`.
2. `getSiteTheme()` returns the persisted value; returns config default when the
   setting is an unknown/absent theme.
3. `getFilamentColors('default')` returns `['primary' => Color::Amber]`;
   `getFilamentColors()` on a theme with no `colors` returns the Amber default.
4. `determineActiveTheme()` returns the site theme when no user/session pref set;
   user pref still wins when present.
5. `ManageSiteSettings` renders an `active_theme` field and persists a new value.
6. A panel provider's resolved primary color reflects the active site theme.

## Out of scope (YAGNI — documented hooks left)

- Compiled per-theme Filament CSS / fonts / radius (`filament_css` key reserved).
- Per-user Filament panel theming (panels are site-wide).
- Shipping additional themes (mechanism proven with `default`; `dark` already on disk).
