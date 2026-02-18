# Module Development Guide

## Table of Contents
- [Introduction](#introduction)
- [Module System Architecture](#module-system-architecture)
- [Module Structure](#module-structure)
- [Creating a Custom Module](#creating-a-custom-module)
- [Module Configuration](#module-configuration)
- [Module Lifecycle Hooks](#module-lifecycle-hooks)
- [Module Dependencies](#module-dependencies)
- [Routes and Controllers](#routes-and-controllers)
- [Database Migrations](#database-migrations)
- [Views and Assets](#views-and-assets)
- [Module Services](#module-services)
- [Filament 5 Integration](#filament-5-integration)
- [Custom Theme Support](#custom-theme-support)
- [Testing Modules](#testing-modules)
- [Best Practices](#best-practices)

## Introduction

This Laravel boilerplate now uses the **internachi/modular** pattern for module management, providing a modern, composer-based approach to modularizing your Laravel application. This system integrates seamlessly with Filament 5 for admin panel functionality and supports custom themes.

### Key Features

- **Composer-based autoloading**: Each module is treated as a composer package
- **Laravel package discovery**: Automatic service provider registration
- **Filament 5 integration**: Auto-discovery of Filament resources, pages, and widgets
- **Custom theme support**: Modules can provide their own themes
- **Backward compatibility**: Supports both old and new module structures
- **IDE-friendly**: Full autocomplete and code navigation support

## Module System Architecture

The module system supports two directory structures:

1. **New modular structure** (recommended): `app-modules/` - follows internachi/modular pattern
2. **Legacy structure**: `app/Modules/` - for backward compatibility

Both structures are supported, but new modules should use the modular pattern in `app-modules/`.

## Module Structure

A typical module following the internachi/modular pattern has this structure:

```
app-modules/YourModule/
├── composer.json                  # Module composer configuration
├── src/
│   ├── YourModuleModule.php      # Main module class
│   ├── Providers/
│   │   └── YourModuleServiceProvider.php
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Models/
│   ├── Services/
│   ├── Filament/                 # Filament 5 resources (auto-discovered)
│   │   ├── Resources/
│   │   ├── Pages/
│   │   └── Widgets/
├── routes/
│   ├── web.php
│   ├── api.php
│   └── admin.php (optional)
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── resources/
│   ├── views/
│   ├── lang/
│   └── assets/
│       ├── css/
│       └── js/
├── config/
│   └── YourModule.php
└── tests/
    ├── Unit/
    └── Feature/
```

## Creating a Custom Module

### Using the Artisan Command

The easiest way to create a new module is using the built-in Artisan command:

```bash
php artisan make:module YourModule
# or
php artisan module create YourModule
```

This will create a complete module structure in `app-modules/YourModule` following the internachi/modular pattern.

### What Gets Created

The command will generate:
- Complete directory structure
- Composer.json for autoloading
- Service provider with Laravel package discovery
- Main module class
- Example controller, model, and migration
- Route files (web, api)
- Basic view template
- Configuration file
- Test scaffolding

### Manual Creation

If you prefer to create a module manually, follow these steps:

1. Create the module directory: `app/Modules/YourModule`
2. Create the main module class
3. Create the module.json file
4. Create the service provider
5. Add routes, controllers, and other components as needed

## Module Configuration

### module.json

Every module must have a `module.json` file that contains metadata:

```json
{
    "name": "YourModule",
    "version": "1.0.0",
    "description": "Description of your module",
    "dependencies": ["AnotherModule"],
    "config": {
        "enabled": false,
        "auto_enable": false
    }
}
```

### Module Class

Create your main module class by extending `BaseModule`:

```php
<?php

namespace App\Modules\YourModule;

use App\Modules\BaseModule;

class YourModuleModule extends BaseModule
{
    protected function onEnable(): void
    {
        // Called when module is enabled
        // Initialize services, register event listeners, etc.
    }

    protected function onDisable(): void
    {
        // Called when module is disabled
        // Clean up resources, unregister listeners, etc.
    }

    protected function onInstall(): void
    {
        // Called when module is installed
        // Seed initial data, create default records, etc.
    }

    protected function onUninstall(): void
    {
        // Called when module is uninstalled
        // Clean up data, remove files, etc.
    }
}
```

## Module Lifecycle Hooks

Modules support both lifecycle methods and a flexible hook system:

### Lifecycle Methods

- `onEnable()` - Called when the module is enabled
- `onDisable()` - Called when the module is disabled
- `onInstall()` - Called when the module is installed
- `onUninstall()` - Called when the module is uninstalled

### Hook System

The hook system allows you to extend module functionality at various points:

```php
// Register a hook
$this->registerHook('before_enable', function($module) {
    // Code to execute before module is enabled
}, priority: 10);

// Execute hooks
$this->executeHook('custom_action', $param1, $param2);

// Available built-in hooks:
// - before_enable
// - after_enable
// - before_disable
// - after_disable
// - before_install
// - after_install
// - before_uninstall
// - after_uninstall
```

### Using Configuration

Modules can use the `Configurable` trait for easy configuration management:

```php
// Get a config value
$value = $this->config('key', 'default');

// Set a config value (runtime only)
$this->setConfig('key', 'value');

// Check if config exists
if ($this->hasConfig('key')) {
    // ...
}

// Get all configuration
$allConfig = $this->getAllConfig();

// Merge configuration
$this->mergeConfig(['key1' => 'value1', 'key2' => 'value2']);
```

## Module Dependencies

Modules can declare dependencies on other modules:

```json
{
    "dependencies": ["CoreModule", "AuthModule"]
}
```

The system will:
- Prevent enabling a module if its dependencies are not enabled
- Prevent disabling a module if other enabled modules depend on it
- Validate dependencies during installation

## Routes and Controllers

### Web Routes (`routes/web.php`)

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Modules\YourModule\Http\Controllers\YourController;

Route::prefix('yourmodule')->group(function () {
    Route::get('/', [YourController::class, 'index'])->name('yourmodule.index');
    Route::get('/{id}', [YourController::class, 'show'])->name('yourmodule.show');
});
```

### API Routes (`routes/api.php`)

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Modules\YourModule\Http\Controllers\Api\YourApiController;

Route::prefix('yourmodule')->group(function () {
    Route::get('/', [YourApiController::class, 'index']);
});
```

### Controllers

```php
<?php

namespace App\Modules\YourModule\Http\Controllers;

use App\Http\Controllers\Controller;

class YourController extends Controller
{
    public function index()
    {
        return view('yourmodule::index');
    }
}
```

## Database Migrations

Place migration files in `database/migrations/`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yourmodule_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yourmodule_items');
    }
};
```

Migrations are automatically run when the module is installed.

## Views and Assets

### Views

Views are stored in `resources/views/` and can be accessed using the module name:

```blade
{{-- resources/views/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <h1>Your Module</h1>
@endsection
```

Access in controller:
```php
return view('yourmodule::index');
```

### Assets

Assets in `resources/assets/` are published to `public/modules/YourModule/` when the module is installed.

Reference in views:
```blade
<link href="{{ asset('modules/YourModule/css/style.css') }}" rel="stylesheet">
<script src="{{ asset('modules/YourModule/js/script.js') }}"></script>
```

## Module Services

Create reusable services in the `Services/` directory:

```php
<?php

namespace App\Modules\YourModule\Services;

class YourService
{
    public function doSomething()
    {
        // Service logic
    }
}
```

Register in service provider:

```php
public function register(): void
{
    $this->app->singleton(YourService::class, function ($app) {
        return new YourService();
    });
}
```

## Testing Modules

Create tests in the `tests/` directory:

```php
<?php

namespace Tests\Modules\YourModule;

use Tests\TestCase;
use App\Modules\YourModule\YourModuleModule;

class YourModuleTest extends TestCase
{
    public function test_module_can_be_enabled()
    {
        $module = new YourModuleModule();
        $module->enable();
        
        $this->assertTrue($module->isEnabled());
    }
}
```

## Best Practices

### 1. Keep Modules Independent
- Minimize dependencies on other modules
- Use events for inter-module communication
- Keep coupling loose

### 2. Use Proper Namespacing
- Follow PSR-4 autoloading standards
- Use consistent naming conventions
- Organize code logically

### 3. Document Your Module
- Add clear descriptions in module.json
- Document public APIs
- Include examples in comments

### 4. Handle Errors Gracefully
- Use try-catch blocks in lifecycle hooks
- Log errors appropriately
- Provide meaningful error messages

### 5. Test Thoroughly
- Write unit tests for services
- Test lifecycle hooks
- Test with and without dependencies

### 6. Version Carefully
- Follow semantic versioning
- Document breaking changes
- Provide migration guides

### 7. Configuration Management
- Use config files for module settings
- Provide sensible defaults
- Allow runtime configuration where appropriate

### 8. Security
- Validate all inputs
- Use Laravel's security features
- Follow OWASP guidelines
- Don't expose sensitive data

### 9. Performance
- Cache when appropriate
- Optimize database queries
- Lazy load resources
- Use queues for heavy operations

### 10. Compatibility
- Test with different PHP/Laravel versions
- Check for breaking changes in dependencies
- Maintain backwards compatibility when possible

## Filament 5 Integration

Modules can integrate seamlessly with Filament 5 by placing Filament resources, pages, and widgets in the appropriate directories. The module system will automatically discover and register them.

### Creating Filament Resources

Place Filament resources in `src/Filament/Resources/`:

```php
<?php

namespace Modules\YourModule\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Modules\YourModule\Models\YourModel;

class YourModelResource extends Resource
{
    protected static ?string $model = YourModel::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-collection';
    
    protected static ?string $navigationGroup = 'Your Module';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Your form fields
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Your table columns
            ]);
    }
}
```

### Filament Pages

Create custom Filament pages in `src/Filament/Pages/`:

```php
<?php

namespace Modules\YourModule\Filament\Pages;

use Filament\Pages\Page;

class CustomPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static string $view = 'yourmodule::filament.pages.custom-page';
}
```

### Filament Widgets

Add widgets in `src/Filament/Widgets/`:

```php
<?php

namespace Modules\YourModule\Filament\Widgets;

use Filament\Widgets\Widget;

class CustomWidget extends Widget
{
    protected static string $view = 'yourmodule::filament.widgets.custom-widget';
}
```

### Auto-Discovery

The ModularServiceProvider automatically discovers Filament components when:
- `config('modular.filament.enabled')` is `true`
- Components are in the correct namespace (`Modules\YourModule\Filament\*`)
- The module is properly autoloaded via composer

## Custom Theme Support

Modules can provide custom themes that integrate with the application's theme system.

### Module Theme Structure

```
app-modules/YourModule/
└── resources/
    └── themes/
        └── your-theme/
            ├── theme.json
            ├── views/
            │   └── layouts/
            │       └── app.blade.php
            ├── css/
            │   └── app.css
            └── js/
                └── app.js
```

### Theme Configuration

Create a `theme.json` file:

```json
{
    "name": "Your Theme",
    "version": "1.0.0",
    "description": "Custom theme for YourModule",
    "author": "Your Name",
    "extends": "default"
}
```

### Registering Theme Assets

In your module's service provider:

```php
public function boot(): void
{
    // Register theme
    $themePath = __DIR__ . '/../../resources/themes/your-theme';
    
    if (is_dir($themePath)) {
        $this->publishes([
            $themePath => public_path('themes/your-theme'),
        ], 'yourmodule-themes');
    }
}
```

### Using Theme in Views

```blade
@extends(theme_layout('app'))

@section('content')
    @themeCss
    
    <h1>Your Module Content</h1>
    
    @themeJs
@endsection
```

### Theme Helpers

The application provides several helpers for working with themes:

```php
// Set active theme
set_theme('your-theme');

// Get active theme
$theme = active_theme();

// Get theme asset path
$asset = theme_asset('images/logo.png');

// Get theme layout
$layout = theme_layout('app');
```

## Managing Modules

### List All Modules
```bash
php artisan module list
```

### Enable a Module
```bash
php artisan module enable Blog
```

### Disable a Module
```bash
php artisan module disable Blog
```

### Install a Module
```bash
php artisan module install Blog
```

### Uninstall a Module
```bash
php artisan module uninstall Blog
```

### Get Module Info
```bash
php artisan module info Blog
```

## Troubleshooting

### Module Not Found
- Ensure the module directory exists in `app/Modules/`
- Check that the module class name matches the directory name
- Verify module.json exists and is valid JSON

### Dependencies Not Met
- Enable required modules first
- Check dependency names match exactly
- Verify dependencies are installed

### Routes Not Working
- Check route files are in `routes/` directory
- Ensure routes are properly registered
- Clear route cache: `php artisan route:clear`

### Views Not Found
- Use the correct view namespace: `yourmodule::viewname`
- Check view files exist in `resources/views/`
- Clear view cache: `php artisan view:clear`

## Conclusion

This modular architecture provides a flexible and maintainable way to extend your Laravel application. By following these guidelines and best practices, you can create robust, reusable modules that integrate seamlessly with the rest of your application.

For more examples, refer to the included `BlogModule` in `app/Modules/BlogModule/`.
