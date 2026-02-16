# Custom Theme System Documentation

The Laravel Boilerplate includes a powerful, flexible theme system that allows you to customize layouts, CSS, and JavaScript on a per-theme basis.

## Overview

The theme system supports:
- **Custom Layout Files**: Theme-specific Blade layouts in `resources/views/themes/{theme_name}/`
- **Custom CSS**: Theme-specific stylesheets in `resources/css/themes/{theme_name}/app.css`
- **Custom JavaScript**: Theme-specific scripts in `resources/js/themes/{theme_name}/app.js`
- **User Preferences**: Save theme preferences per user or in session
- **Dynamic Theme Switching**: Switch themes on the fly with Livewire component

## Directory Structure

```
resources/
├── views/themes/
│   ├── default/
│   │   ├── theme.json          # Theme configuration
│   │   └── layouts/
│   │       └── app.blade.php   # Custom layout
│   └── dark/
│       ├── theme.json
│       └── layouts/
│           └── app.blade.php
├── css/themes/
│   ├── default/
│   │   └── app.css             # Theme-specific CSS
│   └── dark/
│       └── app.css
└── js/themes/
    ├── default/
    │   └── app.js              # Theme-specific JS
    └── dark/
        └── app.js
```

## Creating a New Theme

### 1. Create Theme Directories

```bash
mkdir -p resources/views/themes/mytheme/layouts
mkdir -p resources/css/themes/mytheme
mkdir -p resources/js/themes/mytheme
```

### 2. Create Theme Configuration

Create `resources/views/themes/mytheme/theme.json`:

```json
{
    "name": "mytheme",
    "label": "My Custom Theme",
    "description": "A beautiful custom theme",
    "version": "1.0.0",
    "author": "Your Name",
    "colors": {
        "primary": "blue",
        "secondary": "cyan"
    }
}
```

### 3. Create Custom Layout

Create `resources/views/themes/mytheme/layouts/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @themeCss
        @themeJs
        
        @livewireStyles
    </head>
    <body>
        <div class="min-h-screen">
            @yield('content')
        </div>
        @livewireScripts
    </body>
</html>
```

### 4. Create Custom CSS

Create `resources/css/themes/mytheme/app.css`:

```css
@import 'tailwindcss';

/* Theme Custom Styles */
:root {
    --theme-primary: theme('colors.blue.600');
    --theme-secondary: theme('colors.cyan.600');
}

@layer components {
    .theme-btn-primary {
        @apply bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded;
    }
}
```

### 5. Create Custom JavaScript

Create `resources/js/themes/mytheme/app.js`:

```javascript
console.log('My custom theme loaded');

document.addEventListener('DOMContentLoaded', function() {
    // Theme-specific initialization
});
```

### 6. Build Assets

```bash
npm run build
```

## Using Themes in Your Application

### In Blade Templates

```blade
{{-- Get current theme --}}
{{ active_theme() }}

{{-- Use theme-specific layout --}}
@extends(theme_layout('app'))

{{-- Include theme CSS and JS --}}
@themeCss
@themeJs

{{-- Generate theme asset URL --}}
<img src="{{ theme_asset('images/logo.png') }}" alt="Logo">
```

### In PHP/Controllers

```php
// Get theme manager
$theme = app(\App\Services\ThemeManager::class);

// Get active theme
$activeTheme = theme()->getActiveTheme();

// Set theme
set_theme('dark');

// Check if theme exists
if (theme()->themeExists('mytheme')) {
    // Theme exists
}

// Get all themes
$themes = theme()->getThemes();
```

### Theme Switcher Component

Add the theme switcher to any view:

```blade
<livewire:theme-switcher />
```

Or include it in your navigation:

```blade
<div class="flex items-center space-x-4">
    <livewire:theme-switcher />
</div>
```

## Blade Directives

The theme system provides several Blade directives:

- `@themeCss` - Includes theme-specific CSS
- `@themeJs` - Includes theme-specific JavaScript
- `@themeAsset('path')` - Generates theme asset URL
- `@themeLayout('app')` - Returns theme layout path

## Helper Functions

- `theme()` - Get ThemeManager instance
- `active_theme()` - Get active theme name
- `theme_asset($path, $theme = null)` - Generate theme asset URL
- `theme_path($theme = null)` - Get theme directory path
- `set_theme($themeName)` - Set active theme
- `theme_layout($layout)` - Get theme layout path

## Configuration

Edit `config/theme.php` to configure:

```php
return [
    'default' => env('THEME_DEFAULT', 'default'),
    'available' => [
        'light' => 'Light Mode',
        'dark' => 'Dark Mode',
        'auto' => 'System Default',
    ],
    'colors' => [
        'primary' => env('THEME_PRIMARY_COLOR', 'gray'),
        // ...
    ],
    'persist' => env('THEME_PERSIST', true),
];
```

## User Preferences

Theme preferences are automatically saved:
- To the database if user is authenticated (`users.theme_preference`)
- To the session for guests

## Fallback Behavior

If a theme doesn't have a custom file, the system falls back to defaults:
- Layout: Falls back to `resources/views/layouts/app.blade.php`
- CSS: Falls back to `resources/css/app.css`
- JS: Falls back to `resources/js/app.js`

## Best Practices

1. **Keep themes self-contained** - Each theme should work independently
2. **Use theme.json** - Document your theme with proper metadata
3. **Test thoroughly** - Test theme switching and asset loading
4. **Optimize assets** - Run `npm run build` for production
5. **Cache considerations** - Clear cache after theme changes: `php artisan cache:clear`

## Vite Integration

The system automatically discovers theme assets. Vite configuration includes:

```javascript
// Theme assets are automatically included
const themeAssets = [
    ...glob.sync("resources/css/themes/*/app.css"),
    ...glob.sync("resources/js/themes/*/app.js"),
];
```

## Example Themes

The boilerplate includes two example themes:

### Default Theme
- Located in `resources/views/themes/default/`
- Light color scheme
- Gray primary colors

### Dark Theme
- Located in `resources/views/themes/dark/`
- Dark color scheme
- Indigo/purple accents
- Forces dark mode on HTML element

## Troubleshooting

**Theme not switching:**
- Clear cache: `php artisan cache:clear`
- Check theme exists in `resources/views/themes/`
- Verify theme.json is valid JSON

**Assets not loading:**
- Run `npm run build`
- Check file paths in theme directories
- Verify Vite is properly configured

**Layout not applying:**
- Use `@extends(theme_layout('app'))` in views
- Check layout file exists in theme directory
- Verify ThemeServiceProvider is registered

## Advanced Usage

### Custom View Paths

The ThemeManager automatically prepends theme view paths to Laravel's view finder, so theme views take precedence over default views.

### Theme Detection

Override theme detection in `ThemeServiceProvider`:

```php
protected function determineActiveTheme(): string
{
    // Custom logic here
    return 'mytheme';
}
```

### Dynamic Theme Loading

Load themes from external sources by extending `ThemeManager`.

## Support

For issues or questions about the theme system, please refer to:
- Repository: https://github.com/liberusoftware/boilerplate-laravel
- Documentation: See README.md
