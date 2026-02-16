# Multi-Language Support - Quick Setup Guide

This guide will help you get started with the multi-language support feature.

## Step 1: Run Migration

The multi-language support requires a `locale` column in the `users` table. Run the migration:

```bash
php artisan migrate
```

This will add the locale column to store each user's language preference.

## Step 2: Clear Cache

After installing the multi-language support, clear the application cache:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Step 3: Add Language Switcher to Your Layout

Add the language switcher component to your application's navigation or header. This component allows users to change their language preference.

### Option 1: In a Blade Layout

Edit your main layout file (e.g., `resources/views/layouts/app.blade.php`) and add:

```blade
<div class="flex items-center space-x-4">
    <!-- Other navigation items -->
    <livewire:language-switcher />
</div>
```

### Option 2: In Jetstream Navigation

If using Jetstream, edit `resources/views/navigation-menu.blade.php`:

```blade
<!-- Add in the navigation section -->
<div class="hidden sm:flex sm:items-center sm:ml-6">
    <!-- Teams Dropdown, Settings, etc. -->
    <livewire:language-switcher />
</div>
```

## Step 4: Generate Initial Translations

Generate translations for all supported languages:

```bash
php artisan translate:generate --source=en
```

This will translate all English strings to Spanish, French, and German.

## Step 5: Test Language Switching

1. Visit your application
2. Click on the language switcher (globe icon)
3. Select a different language
4. The page will reload with the new language

## Step 6: Using Translations in Your Code

### In Blade Views

Replace hardcoded text with translation keys:

```blade
<!-- Before -->
<h1>Welcome</h1>

<!-- After -->
<h1>{{ __('messages.welcome') }}</h1>
```

### In Controllers

```php
public function index()
{
    return view('dashboard', [
        'title' => __('messages.dashboard'),
    ]);
}
```

### In Notifications

```php
public function toArray($notifiable)
{
    return [
        'message' => __('messages.notification_text'),
    ];
}
```

## Step 7: Managing Translations via Admin Panel

1. Log in to the admin panel at `/admin`
2. Navigate to **Settings** > **Languages**
3. Use the **"Generate Translations"** button to:
   - Select source language (default: English)
   - Select target language (or leave empty for all)
   - Toggle "Overwrite" to update existing translations
   - Click "Generate"

## Adding More Languages

To add support for additional languages:

1. **Update Configuration**

Edit `config/app.php`:

```php
'supported_locales' => [
    'en' => 'English',
    'es' => 'Español',
    'fr' => 'Français',
    'de' => 'Deutsch',
    'it' => 'Italiano',  // Add Italian
    'pt' => 'Português', // Add Portuguese
    // Add more languages...
],
```

2. **Update TranslationService**

Edit `app/Services/TranslationService.php`:

```php
public const SUPPORTED_LANGUAGES = [
    'en' => 'English',
    'es' => 'Spanish',
    'fr' => 'French',
    'de' => 'German',
    'it' => 'Italian',    // Add Italian
    'pt' => 'Portuguese', // Add Portuguese
];
```

3. **Update LanguageSwitcher Component**

Edit `app/Livewire/LanguageSwitcher.php` mount method to use the config:

```php
public function mount()
{
    $this->currentLocale = App::getLocale();
    $this->availableLocales = config('app.supported_locales');
}
```

4. **Create Language Directory**

```bash
mkdir lang/it
mkdir lang/pt
```

5. **Generate Translations**

```bash
php artisan translate:generate --source=en --target=it
php artisan translate:generate --source=en --target=pt
```

## Common Translation Keys

Here are the common translation keys included in `lang/*/messages.php`:

- `welcome` - Welcome message
- `dashboard` - Dashboard
- `profile` - Profile
- `logout` - Logout
- `login` - Login
- `register` - Register
- `settings` - Settings
- `users` - Users
- `teams` - Teams
- `messages` - Messages
- `notifications` - Notifications
- `search` - Search
- `edit` - Edit
- `create` - Create
- `delete` - Delete
- `save` - Save
- `cancel` - Cancel
- `language` - Language
- `select_language` - Select Language

## Tips for Better Translations

1. **Use Descriptive Keys**: Use keys like `auth.login` instead of just `login`
2. **Group Related Translations**: Create separate files for different sections
3. **Test Each Language**: Switch to each language and verify translations
4. **Use Parameters**: For dynamic content, use parameters:
   ```blade
   {{ __('messages.greeting', ['name' => $user->name]) }}
   ```
5. **Review Automated Translations**: Automated translations may need manual review for accuracy

## Troubleshooting

### Language Switcher Not Showing

- Make sure Alpine.js is loaded (Livewire includes it)
- Check browser console for JavaScript errors
- Clear view cache: `php artisan view:clear`

### Translations Not Updating

- Clear cache: `php artisan cache:clear`
- Clear config: `php artisan config:clear`
- Regenerate translations: `php artisan translate:generate --force`

### Wrong Language Displayed

- Check user's language preference in database
- Clear session: Delete cookies and refresh
- Verify middleware is registered in `app/Http/Kernel.php`

## Advanced Usage

### Detect User's Browser Language

The middleware automatically detects the user's preferred language from their browser settings if they haven't set a preference.

### Force a Specific Language

You can force a specific language for a request:

```php
use Illuminate\Support\Facades\App;

App::setLocale('es');
```

### Get Current Language

```php
$currentLocale = App::getLocale();
```

### Switch Language via URL

Add `?locale=es` to any URL to switch language:

```
https://yourdomain.com/dashboard?locale=es
```

## Next Steps

- Read the full documentation: [docs/MULTI_LANGUAGE.md](MULTI_LANGUAGE.md)
- Explore translation files in `lang/` directory
- Test the language switcher component
- Generate translations for your custom strings
- Review and refine automated translations

## Support

For issues or questions:
- Check the documentation
- Review test files for examples
- Open an issue on GitHub

---

**Happy translating! 🌍**
