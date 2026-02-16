# Multi-Language Support Documentation

This Laravel boilerplate now includes comprehensive multi-language support with automated translations.

## Features

- **Multiple Language Support**: English, Spanish, French, and German out of the box
- **Automated Translations**: Uses MyMemory Translation API (free, no API key required)
- **Smart Language Detection**: 
  - Browser language preferences
  - User-specific preferences
  - Session-based switching
  - URL parameter support
- **Translation Caching**: Improves performance by caching translations for 30 days
- **Admin Interface**: Filament admin panel integration for managing translations
- **Artisan Commands**: CLI tools for generating translations

## Quick Start

### 1. Run the Migration

Add the locale column to the users table:

```bash
php artisan migrate
```

### 2. Add Language Switcher to Your Layout

Add the language switcher component to your navigation:

```blade
<livewire:language-switcher />
```

### 3. Use Translations in Your Views

Use Laravel's built-in translation helpers:

```blade
{{ __('messages.welcome') }}
{{ trans('messages.dashboard') }}
@lang('messages.profile')
```

### 4. Generate Translations

#### Via Admin Panel
1. Navigate to **Admin > Settings > Languages**
2. Click **"Generate Translations"**
3. Select source and target languages
4. Click **"Generate"**

#### Via Artisan Command
```bash
# Translate all languages from English
php artisan translate:generate --source=en

# Translate to a specific language
php artisan translate:generate --source=en --target=es

# Overwrite existing translations
php artisan translate:generate --source=en --force

# Translate a specific file
php artisan translate:generate --source=en --file=messages
```

## Configuration

### Supported Locales

Edit `config/app.php` to add or remove languages:

```php
'supported_locales' => [
    'en' => 'English',
    'es' => 'Español',
    'fr' => 'Français',
    'de' => 'Deutsch',
    'it' => 'Italiano',  // Add more languages
],
```

### Default Locale

Set the default application locale:

```php
'locale' => 'en',
'fallback_locale' => 'en',
```

## Translation Files

Translation files are stored in the `lang/` directory:

```
lang/
├── en/
│   ├── messages.php
│   └── validation.php
├── es/
│   └── messages.php
├── fr/
│   └── messages.php
└── de/
    └── messages.php
```

### Creating Translation Files

Create a new translation file:

```php
<?php
// lang/en/messages.php

return [
    'welcome' => 'Welcome',
    'dashboard' => 'Dashboard',
    'profile' => 'Profile',
    // Add more translations...
];
```

## Usage Examples

### In Blade Templates

```blade
<!-- Simple translation -->
<h1>{{ __('messages.welcome') }}</h1>

<!-- Translation with parameters -->
<p>{{ __('messages.greeting', ['name' => $user->name]) }}</p>

<!-- Pluralization -->
<p>{{ trans_choice('messages.items', $count) }}</p>
```

### In Controllers

```php
use Illuminate\Support\Facades\App;

public function index()
{
    // Get current locale
    $locale = App::getLocale();
    
    // Set locale dynamically
    App::setLocale('es');
    
    // Use translation
    $message = __('messages.welcome');
    
    return view('dashboard', compact('message'));
}
```

### In Livewire Components

```php
public function render()
{
    return view('livewire.component', [
        'title' => __('messages.dashboard'),
    ]);
}
```

### In JavaScript (via Blade)

```javascript
const translations = {
    welcome: "{{ __('messages.welcome') }}",
    dashboard: "{{ __('messages.dashboard') }}",
};

console.log(translations.welcome);
```

## API Reference

### TranslationService

The `TranslationService` class provides programmatic access to translations:

```php
use App\Services\TranslationService;

$service = app(TranslationService::class);

// Translate a single text
$translated = $service->translate('Hello', 'es', 'en');

// Translate multiple texts
$translations = $service->translateBatch([
    'hello' => 'Hello',
    'world' => 'World',
], 'es', 'en');

// Get supported languages
$languages = $service->getSupportedLanguages();

// Check if language is supported
if ($service->isLanguageSupported('es')) {
    // Do something
}
```

### SetLocale Middleware

The middleware is automatically applied to the `web` middleware group. It:

1. Checks for `locale` query parameter
2. Falls back to session value
3. Uses authenticated user's preference
4. Detects from browser's Accept-Language header
5. Uses the default locale

### Language Switcher Component

Properties:
- `$currentLocale`: Current application locale
- `$availableLocales`: Array of available languages

Methods:
- `switchLanguage($locale)`: Switch to a different language

## User Preferences

Users can set their preferred language:

```php
// Update user's language preference
$user->update(['locale' => 'es']);

// Get user's language preference
$locale = $user->locale;
```

The middleware automatically uses the authenticated user's preferred language.

## Advanced Features

### Custom Translation Provider

You can extend the `TranslationService` to use a different translation API:

```php
namespace App\Services;

class CustomTranslationService extends TranslationService
{
    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        // Your custom translation logic
        return $translatedText;
    }
}
```

Register it in a service provider:

```php
$this->app->bind(TranslationService::class, CustomTranslationService::class);
```

### URL-based Language Switching

Add a route for language switching:

```php
Route::get('/language/{locale}', function ($locale) {
    if (in_array($locale, array_keys(config('app.supported_locales')))) {
        session(['locale' => $locale]);
        
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }
    }
    
    return redirect()->back();
})->name('language.switch');
```

## Testing

Run the translation tests:

```bash
# Run all tests
php artisan test

# Run translation-specific tests
php artisan test --filter=Translation
php artisan test --filter=Language
php artisan test --filter=SetLocale
```

## Troubleshooting

### Translations Not Working

1. Clear cache: `php artisan cache:clear`
2. Clear config: `php artisan config:clear`
3. Clear views: `php artisan view:clear`
4. Verify translation files exist in `lang/{locale}/`

### API Rate Limiting

The free MyMemory API has rate limits. If you encounter issues:

1. Use the `--force` flag sparingly
2. Add delays between translation batches (already implemented)
3. Consider caching translations more aggressively
4. For production, consider a paid translation API

### Missing Translations

If a translation key is missing:

1. It will fall back to the key name
2. Check your translation files for the key
3. Generate translations using the command or admin panel
4. Add the key manually to translation files

## Best Practices

1. **Use Translation Keys**: Always use descriptive keys like `messages.welcome` instead of hardcoding text
2. **Organize by Context**: Create separate translation files for different sections (auth.php, messages.php, etc.)
3. **Cache Translations**: The system automatically caches API translations
4. **Test in Different Languages**: Always test your application in multiple languages
5. **Use Pluralization**: Use `trans_choice()` for strings that need pluralization
6. **Parameter Naming**: Use descriptive parameter names in translations: `__('messages.greeting', ['userName' => $name])`

## Security Considerations

- Translation API calls are cached to prevent excessive requests
- User locale preferences are validated against supported locales
- Session-based language switching is CSRF-protected
- No sensitive data is sent to the translation API

## Performance

- Translations are cached for 30 days
- Browser language detection is efficient
- Minimal database queries for user preferences
- Translation files are compiled by Laravel for optimal performance

## Future Enhancements

Potential improvements for the translation system:

1. **More Languages**: Add support for additional languages
2. **RTL Support**: Add right-to-left language support (Arabic, Hebrew)
3. **Translation Management UI**: Web interface for editing translations
4. **Import/Export**: Bulk import/export of translation files
5. **Translation Memory**: Store and reuse translations across the system
6. **Professional Translation**: Integration with professional translation services

## Support

For issues or questions about the multi-language support:

1. Check this documentation
2. Review the test files for usage examples
3. Open an issue on GitHub
4. Contact the development team

---

**Version**: 1.0.0  
**Last Updated**: February 2026  
**Maintainer**: Liberu Software
