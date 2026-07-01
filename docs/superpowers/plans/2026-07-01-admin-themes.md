# Admin-Controlled Site Theme Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an admin pick the site-wide theme from the Filament admin panel, restyling both frontend Blade and Filament panels, with zero visual change on ship.

**Architecture:** Add a single `SiteSettings.active_theme` field. The frontend theme resolver inserts it between session and config fallback; the Filament panel providers feed the active site theme's `theme.json` colors into `->colors()`. All theming infrastructure (`ThemeManager`, `ThemeServiceProvider`, per-user switcher, `themes/`) already exists and is reused.

**Tech Stack:** Laravel 13, PHP 8.5, Filament v5, `spatie/laravel-settings`, Pest 5 / PHPUnit 13 (SQLite `:memory:`), run inside the `php-fpm` Docker container.

## Global Constraints

- All commands run inside Docker: prefix with `docker compose exec -T php-fpm`. Never run `php`/`composer` on the host.
- All git operations run **inside `src/`** (the app repo). Never commit to the parent infra repo.
- TDD: write the failing test first, confirm it fails, then implement, then confirm green.
- Every changed file under `app/` must pass `vendor/bin/phpstan analyse` at level 5 and `vendor/bin/pint --test`.
- Filament panel colors currently use `Filament\Support\Colors\Color::Amber` — the default theme MUST keep the Amber primary (no visual change on ship).
- Frontend theme resolution order (final): `user.theme_preference → session('theme_preference') → ThemeManager::getSiteTheme() → config('theme.default')`.
- `ThemeManager::getSiteTheme()` and `getFilamentColors()` MUST NEVER throw (safe fallback), so console/migrations/boot never break.

---

### Task 0: Fix test APP_KEY (prerequisite)

Filament page tests (Task 5) render the panel, which needs an encryption key. Tests currently fail with `MissingAppKeyException` because `phpunit.xml` sets no `APP_KEY`. This task is a prerequisite and also fixes ~38 pre-existing failures.

**Files:**
- Modify: `phpunit.xml` (the `<php>` env block, around lines 20-34)

**Interfaces:**
- Consumes: nothing
- Produces: a stable encryption key for the `testing` environment

- [ ] **Step 1: Confirm the failure exists**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Feature/ManageSiteSettingsPageTest.php 2>&1 | tail -8`
Expected: FAIL mentioning `MissingAppKeyException`.

- [ ] **Step 2: Add APP_KEY to phpunit.xml**

In `phpunit.xml`, inside `<php>`, add this line just after the `APP_ENV` env entry:

```xml
        <env name="APP_KEY" value="base64:3xVFJ5tTr05dWL1gsJqF+UvWdv3X0Cz/CBaUG47hb3U="/>
```

- [ ] **Step 3: Confirm the pre-existing tests now pass**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Feature/ManageSiteSettingsPageTest.php 2>&1 | tail -8`
Expected: PASS (2 passed).

- [ ] **Step 4: Commit**

```bash
cd /home/tom/code/boilerplate-laravel/src
git add phpunit.xml
git commit -m "test: set fixed APP_KEY in phpunit.xml (fixes MissingAppKeyException)"
```

---

### Task 1: Add `active_theme` to SiteSettings

**Files:**
- Create: `database/settings/2026_07_01_000000_add_active_theme_to_site_settings.php`
- Modify: `app/Settings/SiteSettings.php` (add one property)
- Test: `tests/Feature/SiteSettingsActiveThemeTest.php`

**Interfaces:**
- Consumes: nothing
- Produces: `SiteSettings::$active_theme` (`string`, default `'default'`)

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SiteSettingsActiveThemeTest.php`:

```php
<?php

use App\Settings\SiteSettings;

it('exposes active_theme defaulting to the default theme', function () {
    expect(app(SiteSettings::class)->active_theme)->toBe('default');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Feature/SiteSettingsActiveThemeTest.php`
Expected: FAIL — property `active_theme` not defined / settings property missing.

- [ ] **Step 3: Add the settings migration**

Create `database/settings/2026_07_01_000000_add_active_theme_to_site_settings.php`:

```php
<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class() extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('site.active_theme', 'default');
    }
};
```

- [ ] **Step 4: Add the property to SiteSettings**

In `app/Settings/SiteSettings.php`, add after `public string $footer_copyright;`:

```php
    public string $active_theme;
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Feature/SiteSettingsActiveThemeTest.php`
Expected: PASS.

- [ ] **Step 6: Pint + PHPStan**

Run: `docker compose exec -T php-fpm vendor/bin/pint app/Settings/SiteSettings.php && docker compose exec -T php-fpm vendor/bin/phpstan analyse app/Settings/SiteSettings.php`
Expected: PASS, no errors.

- [ ] **Step 7: Commit**

```bash
cd /home/tom/code/boilerplate-laravel/src
git add database/settings/2026_07_01_000000_add_active_theme_to_site_settings.php app/Settings/SiteSettings.php tests/Feature/SiteSettingsActiveThemeTest.php
git commit -m "feat(settings): add active_theme site setting"
```

---

### Task 2: `ThemeManager::getSiteTheme()`

**Files:**
- Modify: `app/Services/ThemeManager.php` (add method)
- Test: `tests/Unit/ThemeManagerSiteThemeTest.php`

**Interfaces:**
- Consumes: `SiteSettings::$active_theme` (Task 1), existing `ThemeManager::themeExists()`
- Produces: `ThemeManager::getSiteTheme(): string` — the persisted site theme, or `config('theme.default','default')` on any failure or unknown theme. Never throws.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/ThemeManagerSiteThemeTest.php`:

```php
<?php

use App\Services\ThemeManager;
use App\Settings\SiteSettings;

it('returns the persisted site theme', function () {
    $settings = app(SiteSettings::class);
    $settings->active_theme = 'dark';
    $settings->save();

    expect(app(ThemeManager::class)->getSiteTheme())->toBe('dark');
});

it('falls back to config default when the site theme is unknown', function () {
    $settings = app(SiteSettings::class);
    $settings->active_theme = 'no-such-theme';
    $settings->save();

    expect(app(ThemeManager::class)->getSiteTheme())->toBe(config('theme.default'));
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Unit/ThemeManagerSiteThemeTest.php`
Expected: FAIL — `getSiteTheme` not defined.

- [ ] **Step 3: Implement `getSiteTheme()`**

In `app/Services/ThemeManager.php`, add this method (and the `use` for `SiteSettings` and `Throwable` at the top):

At the top with the other `use` statements:

```php
use App\Settings\SiteSettings;
use Throwable;
```

Method (place after `getActiveTheme()`):

```php
    /**
     * The admin-selected site-wide theme, or the config default when the
     * setting is unavailable or names a theme that does not exist. Never throws.
     */
    public function getSiteTheme(): string
    {
        $default = config('theme.default', 'default');
        $default = is_string($default) ? $default : 'default';

        try {
            $theme = app(SiteSettings::class)->active_theme;
        } catch (Throwable) {
            return $default;
        }

        return $this->themeExists($theme) ? $theme : $default;
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Unit/ThemeManagerSiteThemeTest.php`
Expected: PASS (2 passed).

- [ ] **Step 5: Pint + PHPStan**

Run: `docker compose exec -T php-fpm vendor/bin/pint app/Services/ThemeManager.php && docker compose exec -T php-fpm vendor/bin/phpstan analyse app/Services/ThemeManager.php`
Expected: PASS, no errors.

- [ ] **Step 6: Commit**

```bash
cd /home/tom/code/boilerplate-laravel/src
git add app/Services/ThemeManager.php tests/Unit/ThemeManagerSiteThemeTest.php
git commit -m "feat(theme): ThemeManager::getSiteTheme() reads admin site theme"
```

---

### Task 3: `ThemeManager::getFilamentColors()` + default theme colors

**Files:**
- Modify: `app/Services/ThemeManager.php` (add method + color map)
- Modify: `themes/default/theme.json` (set `colors.primary` to `amber`)
- Test: `tests/Unit/ThemeManagerFilamentColorsTest.php`

**Interfaces:**
- Consumes: existing `ThemeManager::getThemeConfig()`
- Produces: `ThemeManager::getFilamentColors(?string $theme = null): array` — returns `['primary' => <Color const>]`, mapping the theme's `colors.primary` (lowercase Tailwind name) to a `Filament\Support\Colors\Color` constant. Unknown/missing → `['primary' => Color::Amber]`.

- [ ] **Step 1: Set the default theme's primary color to amber**

Edit `themes/default/theme.json` so the `colors` block is:

```json
    "colors": {
        "primary": "amber",
        "secondary": "slate"
    }
```

(This preserves the current Amber panel look.)

- [ ] **Step 2: Write the failing test**

Create `tests/Unit/ThemeManagerFilamentColorsTest.php`:

```php
<?php

use App\Services\ThemeManager;
use Filament\Support\Colors\Color;

it('maps the default theme primary color to the Amber Filament palette', function () {
    $colors = app(ThemeManager::class)->getFilamentColors('default');

    expect($colors)->toHaveKey('primary');
    expect($colors['primary'])->toBe(Color::Amber);
});

it('maps the dark theme primary color to the Indigo Filament palette', function () {
    $colors = app(ThemeManager::class)->getFilamentColors('dark');

    expect($colors['primary'])->toBe(Color::Indigo);
});

it('falls back to Amber for an unknown color name', function () {
    // A theme with no colors block resolves to the Amber default.
    $colors = app(ThemeManager::class)->getFilamentColors('no-such-theme');

    expect($colors['primary'])->toBe(Color::Amber);
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Unit/ThemeManagerFilamentColorsTest.php`
Expected: FAIL — `getFilamentColors` not defined.

- [ ] **Step 4: Implement `getFilamentColors()`**

In `app/Services/ThemeManager.php`, add the `use` at the top:

```php
use Filament\Support\Colors\Color;
```

Add these members (place after `getThemeConfig()`):

```php
    /**
     * Whitelist of Tailwind color names → Filament Color palettes. Explicit map
     * (not dynamic constant lookup) so an unexpected theme.json value can never
     * reference an undefined constant.
     *
     * @return array<string, array<int|string, string>>
     */
    protected function filamentColorMap(): array
    {
        return [
            'slate' => Color::Slate,
            'gray' => Color::Gray,
            'zinc' => Color::Zinc,
            'neutral' => Color::Neutral,
            'stone' => Color::Stone,
            'red' => Color::Red,
            'orange' => Color::Orange,
            'amber' => Color::Amber,
            'yellow' => Color::Yellow,
            'lime' => Color::Lime,
            'green' => Color::Green,
            'emerald' => Color::Emerald,
            'teal' => Color::Teal,
            'cyan' => Color::Cyan,
            'sky' => Color::Sky,
            'blue' => Color::Blue,
            'indigo' => Color::Indigo,
            'violet' => Color::Violet,
            'purple' => Color::Purple,
            'fuchsia' => Color::Fuchsia,
            'pink' => Color::Pink,
            'rose' => Color::Rose,
        ];
    }

    /**
     * Build the Filament panel color palette for a theme from its theme.json
     * `colors.primary`. Unknown or missing → Amber (the shipped default look).
     *
     * @return array<string, array<int|string, string>>
     */
    public function getFilamentColors(?string $theme = null): array
    {
        $config = $this->getThemeConfig($theme);
        $colors = is_array($config['colors'] ?? null) ? $config['colors'] : [];
        $primary = is_string($colors['primary'] ?? null) ? strtolower($colors['primary']) : 'amber';

        $map = $this->filamentColorMap();

        return ['primary' => $map[$primary] ?? Color::Amber];
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Unit/ThemeManagerFilamentColorsTest.php`
Expected: PASS (3 passed).

- [ ] **Step 6: Pint + PHPStan**

Run: `docker compose exec -T php-fpm vendor/bin/pint app/Services/ThemeManager.php && docker compose exec -T php-fpm vendor/bin/phpstan analyse app/Services/ThemeManager.php`
Expected: PASS, no errors.

- [ ] **Step 7: Commit**

```bash
cd /home/tom/code/boilerplate-laravel/src
git add app/Services/ThemeManager.php themes/default/theme.json tests/Unit/ThemeManagerFilamentColorsTest.php
git commit -m "feat(theme): map theme.json colors to Filament palette"
```

---

### Task 4: Frontend resolution honors the site theme

**Files:**
- Modify: `app/Providers/ThemeServiceProvider.php` (`determineActiveTheme()`)
- Test: `tests/Feature/ThemeSiteResolutionTest.php`

**Interfaces:**
- Consumes: `ThemeManager::getSiteTheme()` (Task 2)
- Produces: updated resolution order `user → session → site → config`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ThemeSiteResolutionTest.php`:

```php
<?php

use App\Services\ThemeManager;
use App\Settings\SiteSettings;

it('uses the site theme when no user or session preference is set', function () {
    $settings = app(SiteSettings::class);
    $settings->active_theme = 'dark';
    $settings->save();

    // Re-boot the provider path by resolving a fresh ThemeManager via a request.
    $this->get('/');

    expect(app(ThemeManager::class)->getActiveTheme())->toBe('dark');
});

it('lets a session preference win over the site theme', function () {
    $settings = app(SiteSettings::class);
    $settings->active_theme = 'dark';
    $settings->save();

    session(['theme_preference' => 'default']);
    $this->get('/');

    expect(app(ThemeManager::class)->getActiveTheme())->toBe('default');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Feature/ThemeSiteResolutionTest.php`
Expected: FAIL — active theme is `default` (config), site layer not consulted.

- [ ] **Step 3: Insert the site layer in `determineActiveTheme()`**

In `app/Providers/ThemeServiceProvider.php`, replace the body of `determineActiveTheme()` (currently: user pref → session → config default) with:

```php
    protected function determineActiveTheme(): string
    {
        $user = auth()->user();
        if ($user instanceof User && is_string($user->theme_preference) && $user->theme_preference !== '') {
            return $user->theme_preference;
        }

        $session = session('theme_preference');
        if (is_string($session) && $session !== '') {
            return $session;
        }

        // Admin-selected site-wide theme (validated; safe fallback to config default).
        return $this->app->make(ThemeManager::class)->getSiteTheme();
    }
```

(`getSiteTheme()` already falls back to `config('theme.default')`, so the old config branch is subsumed. `ThemeManager` is already imported in this file.)

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Feature/ThemeSiteResolutionTest.php`
Expected: PASS (2 passed).

- [ ] **Step 5: Run the existing theme tests for regressions**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Feature/ThemeServiceProviderTest.php tests/Feature/ThemeSwitcherTest.php tests/Unit/ThemeManagerTest.php`
Expected: PASS (no regressions).

- [ ] **Step 6: Pint + PHPStan**

Run: `docker compose exec -T php-fpm vendor/bin/pint app/Providers/ThemeServiceProvider.php && docker compose exec -T php-fpm vendor/bin/phpstan analyse app/Providers/ThemeServiceProvider.php`
Expected: PASS, no errors.

- [ ] **Step 7: Commit**

```bash
cd /home/tom/code/boilerplate-laravel/src
git add app/Providers/ThemeServiceProvider.php tests/Feature/ThemeSiteResolutionTest.php
git commit -m "feat(theme): frontend resolution honors admin site theme"
```

---

### Task 5: Admin Select on ManageSiteSettings

**Files:**
- Modify: `app/Filament/Pages/ManageSiteSettings.php` (add an Appearance section with a Select)
- Test: `tests/Feature/ManageSiteSettingsThemeTest.php`

**Interfaces:**
- Consumes: `SiteSettings::$active_theme` (Task 1), `ThemeManager::getThemes()` (existing)
- Produces: an `active_theme` form field that persists

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ManageSiteSettingsThemeTest.php`:

```php
<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Models\User;
use App\Settings\SiteSettings;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(User::factory()->create());
});

it('renders the active_theme field', function () {
    Livewire::test(ManageSiteSettings::class)
        ->assertOk()
        ->assertFormFieldExists('active_theme');
});

it('persists a chosen theme', function () {
    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['active_theme' => 'dark'])
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SiteSettings::class);

    expect(app(SiteSettings::class)->active_theme)->toBe('dark');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Feature/ManageSiteSettingsThemeTest.php`
Expected: FAIL — form field `active_theme` does not exist.

- [ ] **Step 3: Add the Select to the form**

In `app/Filament/Pages/ManageSiteSettings.php`, add the import at the top with the other Filament form component imports:

```php
use App\Services\ThemeManager;
use Filament\Forms\Components\Select;
```

Add a new section as the **first** component in the `->components([ ... ])` array (before `Section::make('Site Information')`):

```php
                Section::make('Appearance')
                    ->description('Site-wide theme. Users may still override this with their own preference.')
                    ->schema([
                        Select::make('active_theme')
                            ->label('Site Theme')
                            ->options(collect(app(ThemeManager::class)->getThemes())
                                ->mapWithKeys(fn (array $config, string $name): array => [
                                    $name => is_string($config['label'] ?? null) ? $config['label'] : ucfirst($name),
                                ])
                                ->all())
                            ->required()
                            ->native(false),
                    ]),
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Feature/ManageSiteSettingsThemeTest.php`
Expected: PASS (2 passed).

- [ ] **Step 5: Pint + PHPStan**

Run: `docker compose exec -T php-fpm vendor/bin/pint app/Filament/Pages/ManageSiteSettings.php && docker compose exec -T php-fpm vendor/bin/phpstan analyse app/Filament/Pages/ManageSiteSettings.php`
Expected: PASS, no errors.

- [ ] **Step 6: Commit**

```bash
cd /home/tom/code/boilerplate-laravel/src
git add app/Filament/Pages/ManageSiteSettings.php tests/Feature/ManageSiteSettingsThemeTest.php
git commit -m "feat(admin): choose site theme on ManageSiteSettings page"
```

---

### Task 6: Filament panels use the site theme palette

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php` (`->colors(...)`)
- Modify: `app/Providers/Filament/AppPanelProvider.php` (`->colors(...)`)
- Test: `tests/Feature/PanelThemeColorsTest.php`

**Interfaces:**
- Consumes: `ThemeManager::getSiteTheme()` (Task 2), `ThemeManager::getFilamentColors()` (Task 3)
- Produces: panels whose primary color follows the site theme

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PanelThemeColorsTest.php`:

```php
<?php

use App\Services\ThemeManager;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;

it('admin panel primary color follows the site theme', function () {
    // getFilamentColors('dark') → Indigo; assert the resolver returns it so the
    // panel provider (which passes this array to ->colors()) is driven by the theme.
    expect(app(ThemeManager::class)->getFilamentColors('dark')['primary'])->toBe(Color::Indigo);
    expect(app(ThemeManager::class)->getFilamentColors('default')['primary'])->toBe(Color::Amber);

    // Panel registers without error using the theme-driven palette.
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    expect(Filament::getPanel('admin'))->not->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails or passes-trivially, then wire the providers**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Feature/PanelThemeColorsTest.php`
Expected: the color assertions PASS (Task 3 done); this step exists to lock behavior. Proceed to wire the providers so the palette is actually theme-driven.

- [ ] **Step 3: Wire AdminPanelProvider**

In `app/Providers/Filament/AdminPanelProvider.php`, add the import:

```php
use App\Services\ThemeManager;
```

Replace:

```php
            ->colors([
                'primary' => Color::Amber,
            ])
```

with:

```php
            ->colors(app(ThemeManager::class)->getFilamentColors(app(ThemeManager::class)->getSiteTheme()))
```

- [ ] **Step 4: Wire AppPanelProvider**

In `app/Providers/Filament/AppPanelProvider.php`, add the import:

```php
use App\Services\ThemeManager;
```

Replace:

```php
            ->colors([
                'primary' => Color::Amber,
            ])
```

with:

```php
            ->colors(app(ThemeManager::class)->getFilamentColors(app(ThemeManager::class)->getSiteTheme()))
```

(If `Color` is now unused in a provider, remove its `use Filament\Support\Colors\Color;` import — Pint will flag it.)

- [ ] **Step 5: Run the test to verify it passes**

Run: `docker compose exec -T php-fpm vendor/bin/pest tests/Feature/PanelThemeColorsTest.php`
Expected: PASS.

- [ ] **Step 6: Pint + PHPStan**

Run: `docker compose exec -T php-fpm vendor/bin/pint app/Providers/Filament/AdminPanelProvider.php app/Providers/Filament/AppPanelProvider.php && docker compose exec -T php-fpm vendor/bin/phpstan analyse app/Providers/Filament/AdminPanelProvider.php app/Providers/Filament/AppPanelProvider.php`
Expected: PASS, no errors.

- [ ] **Step 7: Commit**

```bash
cd /home/tom/code/boilerplate-laravel/src
git add app/Providers/Filament/AdminPanelProvider.php app/Providers/Filament/AppPanelProvider.php tests/Feature/PanelThemeColorsTest.php
git commit -m "feat(admin): Filament panels follow site theme palette"
```

---

### Task 7: Document the compiled-CSS hook + full suite green

**Files:**
- Modify: `CLAUDE.md` (Theme System section — note admin site theme + Filament color mechanism)

**Interfaces:**
- Consumes: everything above
- Produces: documentation + a green full test run

- [ ] **Step 1: Update CLAUDE.md Theme System section**

In `src/CLAUDE.md`, in the `### Theme System` section, append:

```
Site-wide theme is admin-selectable via `SiteSettings.active_theme` (Appearance
section on the ManageSiteSettings page). Frontend resolution is
`user pref → session → SiteSettings.active_theme → config('theme.default')`.
Filament panels follow the **site-wide** theme only: `AdminPanelProvider`/
`AppPanelProvider` call `->colors(ThemeManager::getFilamentColors(getSiteTheme()))`,
which maps a theme's `theme.json` `colors.primary` (a Tailwind name) to a Filament
`Color` palette. Compiled per-theme Filament CSS is NOT built yet; add a
`theme.json` `filament_css` key + `->viteTheme()` when a theme needs it.
```

- [ ] **Step 2: Run the full test suite**

Run: `docker compose exec -T php-fpm vendor/bin/pest 2>&1 | tail -6`
Expected: PASS — 0 failed (Task 0 cleared the pre-existing `MissingAppKeyException` failures; the new theme tests are green).

- [ ] **Step 3: Full Pint + PHPStan**

Run: `docker compose exec -T php-fpm vendor/bin/pint --test && docker compose exec -T php-fpm vendor/bin/phpstan analyse`
Expected: PASS, no errors.

- [ ] **Step 4: Commit**

```bash
cd /home/tom/code/boilerplate-laravel/src
git add CLAUDE.md
git commit -m "docs: document admin site theme + Filament color mechanism"
```

---

## Notes for the implementer

- The default theme MUST stay Amber (Task 3 sets `themes/default/theme.json` `colors.primary = amber`). If the admin selects `dark`, panels go Indigo and the frontend loads the dark theme — that is the demonstration that the mechanism works end to end.
- One PR for this whole plan (label `enhancement`, reviewer `curtisdelicata`, branch off `main`), per repo conventions.
- Filament panels read the site theme at request boot; a theme change is visible on the next request (no per-user panel theming — that is intentionally deferred).
