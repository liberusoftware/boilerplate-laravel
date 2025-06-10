# Modular Architecture Documentation

## Overview

This Laravel boilerplate includes a comprehensive modular architecture that allows for easy integration of custom modules and functionalities. The system provides a structured way to organize, manage, and extend application features through self-contained modules.

## Architecture Components

### Core Components

1. **ModuleServiceProvider** - Automatically discovers and registers modules
2. **ModuleManager** - Manages module lifecycle (enable, disable, install, uninstall)
3. **BaseModule** - Abstract base class for all modules
4. **ModuleInterface** - Contract that all modules must implement

### Module Structure

Each module follows a standardized directory structure:

```
app/Modules/YourModule/
├── YourModule.php              # Main module class
├── module.json                 # Module metadata
├── Providers/
│   └── YourModuleServiceProvider.php
├── Http/
│   ├── Controllers/
│   └── Middleware/
├── Models/
├── Services/
├── resources/
│   ├── views/
│   ├── lang/
│   └── assets/
├── routes/
│   ├── web.php
│   ├── api.php
│   └── admin.php
├── database/
│   ├── migrations/
│   └── seeders/
├── config/
└── tests/
```

## Creating a Custom Module

### 1. Using the Artisan Command

```bash
php artisan module create YourModuleName
```

This command creates the complete module structure with all necessary files.

### 2. Manual Creation

1. Create the module directory structure
2. Implement the module class extending `BaseModule`
3. Create the service provider
4. Define module metadata in `module.json`

### Example Module Class

```php
<?php

namespace App\Modules\YourModule;

use App\Modules\BaseModule;

class YourModule extends BaseModule
{
    protected function onEnable(): void
    {
        // Called when module is enabled
    }

    protected function onDisable(): void
    {
        // Called when module is disabled
    }

    protected function onInstall(): void
    {
        // Called when module is installed
    }

    protected function onUninstall(): void
    {
        // Called when module is uninstalled
    }
}
```

### Module Metadata (module.json)

```json
{
    "name": "YourModule",
    "version": "1.0.0",
    "description": "Description of your module",
    "dependencies": ["RequiredModule"],
    "config": {
        "setting1": "value1",
        "setting2": "value2"
    }
}
```

## Module Management

### Command Line Interface

```bash
# List all modules
php artisan module list

# Enable a module
php artisan module enable ModuleName

# Disable a module
php artisan module disable ModuleName

# Install a module
php artisan module install ModuleName

# Uninstall a module
php artisan module uninstall ModuleName

# Show module information
php artisan module info ModuleName

# Create a new module
php artisan module create ModuleName
```

### Programmatic Management

```php
use App\Modules\ModuleManager;

$moduleManager = app(ModuleManager::class);

// Get all modules
$modules = $moduleManager->all();

// Get enabled modules
$enabledModules = $moduleManager->enabled();

// Enable a module
$moduleManager->enable('ModuleName');

// Disable a module
$moduleManager->disable('ModuleName');

// Check if module exists
if ($moduleManager->has('ModuleName')) {
    // Module exists
}
```

### Admin Panel Management

Access the module management interface through the Filament admin panel:
- Navigate to System > Modules
- View all modules with their status
- Enable/disable modules with a single click
- Install/uninstall modules
- View detailed module information

## Module Features

### Automatic Registration

The system automatically registers:
- Service providers
- Routes (web, api, admin)
- Views with namespacing
- Translations
- Migrations
- Configuration files

### Dependency Management

Modules can declare dependencies on other modules:
- Dependencies are checked before enabling/installing
- Prevents disabling modules that other modules depend on
- Ensures proper loading order

### Configuration Management

Each module can have its own configuration:
- Stored in `config/` directory within the module
- Automatically merged with application config
- Can be published to main config directory

### Asset Management

Module assets are automatically handled:
- Assets stored in `resources/assets/`
- Published to `public/modules/ModuleName/`
- Automatic publishing during installation

### View Integration

Module views are automatically registered:
- Views stored in `resources/views/`
- Accessible via `view('module_name::view_name')`
- Supports Blade templating

### Route Integration

Module routes are automatically loaded:
- Web routes for frontend functionality
- API routes for REST endpoints
- Admin routes for backend integration

## Testing Modules

### Unit Testing

```php
use Tests\TestCase;
use App\Modules\ModuleManager;

class YourModuleTest extends TestCase
{
    public function test_module_can_be_enabled()
    {
        $moduleManager = app(ModuleManager::class);
        $result = $moduleManager->enable('YourModule');
        $this->assertTrue($result);
    }
}
```

### Feature Testing

```php
public function test_module_routes_work()
{
    $response = $this->get('/your-module');
    $response->assertStatus(200);
}
```

## Best Practices

### Module Design

1. **Single Responsibility** - Each module should have a clear, focused purpose
2. **Loose Coupling** - Minimize dependencies between modules
3. **Clear Interfaces** - Use contracts and interfaces for module interactions
4. **Configuration** - Make modules configurable through config files

### Development Guidelines

1. **Naming Conventions** - Use PascalCase for module names
2. **Namespace Organization** - Follow PSR-4 autoloading standards
3. **Documentation** - Document module functionality and configuration
4. **Testing** - Write comprehensive tests for module functionality

### Performance Considerations

1. **Lazy Loading** - Only load modules when needed
2. **Caching** - Cache module information in production
3. **Asset Optimization** - Minimize and optimize module assets
4. **Database Queries** - Optimize database interactions within modules

## Configuration

### Module System Configuration

Edit `config/modules.php` to customize:
- Module discovery path
- Caching settings
- Auto-discovery behavior
- Default enabled modules

### Environment Variables

```env
MODULES_CACHE=true
APP_DEBUG=false
```

## Troubleshooting

### Common Issues

1. **Module Not Found** - Check module directory structure and naming
2. **Dependencies Not Met** - Ensure required modules are installed and enabled
3. **Routes Not Working** - Verify route files exist and are properly formatted
4. **Views Not Loading** - Check view namespace and file paths

### Debug Mode

Enable debug mode for additional module information:
```env
APP_DEBUG=true
```

## Security Considerations

1. **Input Validation** - Validate all module inputs
2. **Authorization** - Implement proper access controls
3. **File Permissions** - Secure module files and directories
4. **Code Review** - Review module code before deployment

## Migration from Legacy Systems

When migrating existing functionality to modules:

1. Identify logical boundaries for module separation
2. Extract related functionality into modules
3. Update dependencies and service registrations
4. Test thoroughly in development environment
5. Deploy modules incrementally

## Support and Contributing

For questions, issues, or contributions related to the modular architecture:

1. Check existing documentation
2. Review example modules
3. Run test suite to verify functionality
4. Submit issues with detailed reproduction steps

## Example: Blog Module

The included BlogModule demonstrates:
- Complete module structure
- Service integration
- Route definition
- View templates
- API endpoints
- Configuration management
- Testing approaches

Use this as a reference when creating your own modules.