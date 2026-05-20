# Theme System - Quick Reference

## Directory Structure

```
/themes/{theme_name}/
├── theme.json           # Theme metadata (required)
├── views/              # Theme-specific views
│   └── layouts/        # Custom layouts
│       └── app.blade.php
├── css/                # Theme stylesheets
│   └── app.css
└── js/                 # Theme JavaScript
    └── app.js
```

## Quick Start

### 1. Create a New Theme

```bash
# Create theme directory structure
mkdir -p themes/mytheme/views/layouts
mkdir -p themes/mytheme/css
mkdir -p themes/mytheme/js

# Create theme.json
cat > themes/mytheme/theme.json << 'EOF'
{
    "name": "mytheme",
    "label": "My Theme",
    "description": "My custom theme",
    "version": "1.0.0",
    "author": "Your Name",
    "colors": {
        "primary": "blue",
        "secondary": "cyan"
    }
}
EOF

# Build assets
npm run build
```

### 2. Use Theme in Blade

```blade
{{-- Extend theme layout --}}
@extends(theme_layout('app'))

@section('content')
    {{-- Include theme assets --}}
    @themeCss
    @themeJs
    
    {{-- Show current theme --}}
    <p>Current theme: {{ active_theme() }}</p>
    
    {{-- Theme asset URL --}}
    <img src="{{ theme_asset('images/logo.png') }}" alt="Logo">
    
    {{-- Theme switcher --}}
    <livewire:theme-switcher />
@endsection
```

### 3. Switch Themes in PHP

```php
// In controller or service
set_theme('dark');

// Get current theme
$theme = active_theme();

// Get theme manager
$manager = theme();

// Check if theme exists
if (theme()->themeExists('mytheme')) {
    set_theme('mytheme');
}
```

## Blade Directives

| Directive | Purpose | Example |
|-----------|---------|---------|
| `@themeCss` | Include theme CSS | `@themeCss` |
| `@themeJs` | Include theme JS | `@themeJs` |
| `@themeAsset('path')` | Theme asset URL | `@themeAsset('images/logo.png')` |
| `@themeLayout('name')` | Get theme layout | `@extends(@themeLayout('app'))` |

## Helper Functions

| Function | Returns | Example |
|----------|---------|---------|
| `theme()` | ThemeManager | `theme()->getThemes()` |
| `active_theme()` | String | `active_theme()` → 'default' |
| `set_theme($name)` | void | `set_theme('dark')` |
| `theme_asset($path)` | String (URL) | `theme_asset('images/logo.png')` |
| `theme_path($theme)` | String (path) | `theme_path('default')` |
| `theme_views_path($theme)` | String (path) | `theme_views_path('default')` |
| `theme_layout($name)` | String | `theme_layout('app')` |

## ThemeManager Methods

```php
$manager = app(\App\Services\ThemeManager::class);

// Get all themes
$themes = $manager->getThemes();

// Get/set active theme
$active = $manager->getActiveTheme();
$manager->setTheme('dark');

// Check theme exists
$exists = $manager->themeExists('mytheme');

// Get theme paths
$themePath = $manager->getThemePath('default');
$viewsPath = $manager->getThemeViewsPath('default');

// Get theme assets
$css = $manager->getThemeCss('default');
$js = $manager->getThemeJs('default');

// Get theme config
$config = $manager->getThemeConfig('default');

// Check for custom layout
$hasLayout = $manager->hasCustomLayout('app', 'default');

// Clear cache
$manager->clearCache();
```

## Common Patterns

### Custom Layout

```blade
{{-- themes/mytheme/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @themeCss
    @themeJs
    
    @livewireStyles
</head>
<body>
    @yield('content')
    @livewireScripts
</body>
</html>
```

### Custom CSS

```css
/* themes/mytheme/css/app.css */
@import 'tailwindcss';

:root {
    --theme-primary: theme('colors.blue.600');
    --theme-secondary: theme('colors.cyan.600');
}

@layer components {
    .theme-btn-primary {
        @apply bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded;
    }
}
```

### Custom JavaScript

```javascript
// themes/mytheme/js/app.js
console.log('My theme loaded');

document.addEventListener('DOMContentLoaded', function() {
    // Theme initialization
    console.log('Theme initialized');
});
```

## Configuration

```php
// config/theme.php
return [
    'default' => env('THEME_DEFAULT', 'default'),
    'persist' => env('THEME_PERSIST', true),
    'colors' => [
        'primary' => env('THEME_PRIMARY_COLOR', 'gray'),
    ],
];
```

## Environment Variables

```env
THEME_DEFAULT=default
THEME_PERSIST=true
THEME_PRIMARY_COLOR=gray
```

## User Preferences

```php
// Save theme for authenticated user
auth()->user()->update(['theme_preference' => 'dark']);

// Get user's theme preference
$userTheme = auth()->user()->theme_preference;

// Theme is auto-loaded from user preference
// via ThemeServiceProvider
```

## Testing

```bash
# Run theme tests
php artisan test --filter ThemeManagerTest

# Test specific method
php artisan test --filter 'can get theme path'
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Theme not switching | Clear cache: `php artisan cache:clear` |
| Assets not loading | Run: `npm run build` |
| Layout not found | Check file exists in `themes/{name}/views/layouts/` |
| Theme not discovered | Verify `theme.json` exists and is valid JSON |

## File Checklist

When creating a new theme, ensure:

- [ ] `theme.json` exists with valid JSON
- [ ] `views/layouts/app.blade.php` exists
- [ ] `css/app.css` exists
- [ ] `js/app.js` exists
- [ ] Assets built with `npm run build`
- [ ] Theme registered (auto-discovered on next request)

## Resources

- **Full Documentation**: `docs/THEME_SYSTEM.md`
- **Implementation Details**: `docs/THEME_IMPLEMENTATION.md`
- **README Section**: See main README.md
- **Example Themes**: `/themes/default/` and `/themes/dark/`
- **Tests**: `tests/Unit/ThemeManagerTest.php`
