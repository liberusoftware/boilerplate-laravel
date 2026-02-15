# External Module Integration Guide

This guide explains how to integrate modules from external sources such as composer packages or custom locations.

## Overview

The External Module Loader allows you to:
- Load modules from composer packages
- Load modules from custom paths
- Register modules programmatically
- Integrate third-party modules without modifying core code

## Loading Modules from Composer Packages

### Step 1: Install a Package with Modules

```bash
composer require vendor/package-with-modules
```

### Step 2: Enable Composer Module Loading

In your `.env` file:

```env
MODULES_LOAD_COMPOSER=true
```

Or in `config/modules.php`:

```php
'load_composer_modules' => true,
```

### Step 3: Package Structure

For a package to be recognized, it should have a `modules/` directory:

```
vendor/your-vendor/your-package/
├── composer.json
├── src/
└── modules/
    └── YourModule/
        ├── YourModuleModule.php
        ├── module.json
        └── ... (other module files)
```

The system will automatically discover and load these modules.

## Loading Modules from Custom Paths

### Method 1: Configuration

Add custom paths in `config/modules.php`:

```php
'external_paths' => [
    base_path('custom-modules'),
    storage_path('app/modules'),
    '/absolute/path/to/modules',
],
```

### Method 2: Service Provider

Create a custom service provider to load modules:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Support\ExternalModuleLoader;
use App\Modules\ModuleManager;

class CustomModuleProvider extends ServiceProvider
{
    public function boot(): void
    {
        $moduleManager = app(ModuleManager::class);
        $loader = new ExternalModuleLoader($moduleManager);

        // Load from custom path
        $loader->loadFromPath(
            base_path('custom-modules'),
            'CustomModules'
        );

        // Load from composer
        if (config('modules.load_composer_modules')) {
            $loader->loadFromComposer();
        }
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\CustomModuleProvider::class,
],
```

### Method 3: Programmatic Registration

Register a specific module programmatically:

```php
use App\Modules\Support\ExternalModuleLoader;
use App\Modules\ModuleManager;

$moduleManager = app(ModuleManager::class);
$loader = new ExternalModuleLoader($moduleManager);

$loader->registerCustomModule(
    '/path/to/your/module',
    'Your\\Namespace\\YourModule'
);
```

## Creating a Composer Package with Modules

### Step 1: Create Package Structure

```
your-vendor/your-package/
├── composer.json
├── src/
│   └── YourPackageServiceProvider.php
└── modules/
    └── ExampleModule/
        ├── ExampleModuleModule.php
        ├── module.json
        ├── Providers/
        ├── Http/
        └── ... (standard module structure)
```

### Step 2: composer.json

```json
{
    "name": "your-vendor/your-package",
    "description": "Package with custom modules",
    "type": "library",
    "require": {
        "php": "^8.4",
        "laravel/framework": "^12.0"
    },
    "autoload": {
        "psr-4": {
            "YourVendor\\YourPackage\\": "src/",
            "YourVendor\\YourPackage\\Modules\\": "modules/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "YourVendor\\YourPackage\\YourPackageServiceProvider"
            ]
        }
    }
}
```

### Step 3: Module Class

```php
<?php

namespace YourVendor\YourPackage\Modules\ExampleModule;

use App\Modules\BaseModule;

class ExampleModuleModule extends BaseModule
{
    protected function onEnable(): void
    {
        // Module initialization
    }
}
```

### Step 4: module.json

```json
{
    "name": "ExampleModule",
    "version": "1.0.0",
    "description": "Example module from composer package",
    "dependencies": [],
    "config": {
        "enabled": false
    }
}
```

## Best Practices

### 1. Namespace Organization

Use proper PSR-4 namespacing:

```
YourVendor\YourPackage\Modules\ModuleName\
```

### 2. Module Discovery

Ensure `module.json` exists in each module directory - it's used for discovery.

### 3. Dependencies

Declare dependencies in both:
- `composer.json` (PHP/Laravel dependencies)
- `module.json` (module dependencies)

### 4. Testing

Test your external modules:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\ModuleManager;

class ExternalModuleTest extends TestCase
{
    public function test_external_module_loads()
    {
        $manager = app(ModuleManager::class);
        
        $this->assertTrue($manager->has('YourModuleName'));
    }
}
```

### 5. Documentation

Include clear documentation in your package:
- Installation instructions
- Configuration options
- Usage examples
- API documentation

## Example: Complete Package Integration

### 1. Create the Package

```bash
mkdir -p vendor/mycompany/analytics-module
cd vendor/mycompany/analytics-module
```

### 2. Package Structure

```
vendor/mycompany/analytics-module/
├── composer.json
├── README.md
├── src/
│   └── AnalyticsServiceProvider.php
└── modules/
    └── Analytics/
        ├── AnalyticsModule.php
        ├── module.json
        ├── Http/
        │   └── Controllers/
        │       └── AnalyticsController.php
        ├── Services/
        │   └── AnalyticsService.php
        ├── routes/
        │   └── web.php
        └── resources/
            └── views/
                └── dashboard.blade.php
```

### 3. composer.json

```json
{
    "name": "mycompany/analytics-module",
    "description": "Analytics module for Laravel",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.4",
        "laravel/framework": "^12.0"
    },
    "autoload": {
        "psr-4": {
            "MyCompany\\Analytics\\": "src/",
            "MyCompany\\Analytics\\Modules\\": "modules/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "MyCompany\\Analytics\\AnalyticsServiceProvider"
            ]
        }
    }
}
```

### 4. Service Provider

```php
<?php

namespace MyCompany\Analytics;

use Illuminate\Support\ServiceProvider;
use App\Modules\Support\ExternalModuleLoader;
use App\Modules\ModuleManager;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $moduleManager = app(ModuleManager::class);
        $loader = new ExternalModuleLoader($moduleManager);
        
        // Load modules from this package
        $loader->loadFromPath(
            __DIR__ . '/../modules',
            'MyCompany\\Analytics\\Modules'
        );
    }
}
```

### 5. Module Class

```php
<?php

namespace MyCompany\Analytics\Modules\Analytics;

use App\Modules\BaseModule;

class AnalyticsModule extends BaseModule
{
    protected function onEnable(): void
    {
        \Log::info('Analytics module enabled');
    }

    protected function onInstall(): void
    {
        // Create default analytics configuration
        \DB::table('analytics_settings')->insert([
            'tracking_enabled' => true,
            'retention_days' => 90,
        ]);
    }
}
```

### 6. Install the Package

```bash
composer require mycompany/analytics-module
```

### 7. Use the Module

```bash
php artisan module list
php artisan module install Analytics
php artisan module enable Analytics
```

## Troubleshooting

### Module Not Discovered

**Problem**: External module not showing in `php artisan module list`

**Solutions**:
1. Ensure `module.json` exists in the module directory
2. Check namespace matches composer.json autoload configuration
3. Run `composer dump-autoload`
4. Check logs in `storage/logs/laravel.log`

### Class Not Found

**Problem**: Module class cannot be instantiated

**Solutions**:
1. Verify PSR-4 namespace matches directory structure
2. Run `composer dump-autoload`
3. Check class name matches expected pattern:
   - `ModuleName\ModuleNameModule`
   - `ModuleName\Module`
   - `ModuleName\ModuleName`

### Dependencies Not Met

**Problem**: Module won't enable due to missing dependencies

**Solutions**:
1. Install required composer packages
2. Enable required modules first
3. Check `module.json` dependencies are correct

### Performance Issues

**Problem**: Slow application boot with many external modules

**Solutions**:
1. Enable module caching in `config/modules.php`:
   ```php
   'cache' => true,
   'cache_ttl' => 3600,
   ```
2. Disable development mode in production:
   ```php
   'development' => false,
   ```
3. Only load composer modules if needed:
   ```php
   'load_composer_modules' => false,
   ```

## Advanced Usage

### Conditional Loading

Load modules based on environment:

```php
if (app()->environment('production')) {
    $loader->loadFromPath($productionModulesPath);
} else {
    $loader->loadFromPath($developmentModulesPath);
}
```

### Custom Module Discovery

Implement custom discovery logic:

```php
class CustomModuleLoader extends ExternalModuleLoader
{
    protected function loadModuleFromDirectory(string $directory, string $baseNamespace): void
    {
        // Custom logic here
        parent::loadModuleFromDirectory($directory, $baseNamespace);
    }
}
```

### Module Versioning

Check module versions before loading:

```php
$moduleJsonPath = $directory . '/module.json';
$moduleData = json_decode(File::get($moduleJsonPath), true);

if (version_compare($moduleData['version'], '2.0.0', '>=')) {
    // Load only if version 2.0.0 or higher
    parent::loadModuleFromDirectory($directory, $baseNamespace);
}
```

## Security Considerations

1. **Validate Sources**: Only load modules from trusted sources
2. **Code Review**: Review third-party module code before installation
3. **Permissions**: Ensure module files have appropriate permissions
4. **Sandbox**: Consider sandboxing external modules in development
5. **Updates**: Keep external modules updated for security patches

## Conclusion

The External Module Loader provides a flexible system for integrating modules from any source. Whether you're using composer packages, custom locations, or programmatic registration, the system adapts to your needs while maintaining security and performance.

For more information:
- [Module Development Guide](MODULE_DEVELOPMENT.md)
- [Quick Start Guide](MODULE_QUICK_START.md)
