# Module Integration Quick Start Guide

This guide will help you quickly integrate custom modules into your Laravel application.

## Quick Start: Creating Your First Module

### Step 1: Generate the Module

```bash
php artisan module create MyModule
```

This creates a complete module structure at `app/Modules/MyModule/`.

### Step 2: Configure the Module

Edit `app/Modules/MyModule/module.json`:

```json
{
    "name": "MyModule",
    "version": "1.0.0",
    "description": "My custom module for specific functionality",
    "dependencies": [],
    "config": {
        "enabled": false
    }
}
```

### Step 3: Add Your Custom Logic

Edit `app/Modules/MyModule/MyModuleModule.php`:

```php
<?php

namespace App\Modules\MyModule;

use App\Modules\BaseModule;

class MyModuleModule extends BaseModule
{
    protected function onEnable(): void
    {
        // Initialize your module
        \Log::info('MyModule enabled');
    }

    protected function onInstall(): void
    {
        // Run installation tasks
        \Log::info('MyModule installed');
    }
}
```

### Step 4: Install and Enable

```bash
# Install the module (runs migrations, publishes assets)
php artisan module install MyModule

# Or just enable it (if already installed)
php artisan module enable MyModule
```

### Step 5: Verify

```bash
php artisan module list
php artisan module info MyModule
```

## Common Use Cases

### 1. Adding Custom Routes

Edit `routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Modules\MyModule\Http\Controllers\MyController;

Route::middleware(['web'])->prefix('mymodule')->group(function () {
    Route::get('/', [MyController::class, 'index'])->name('mymodule.index');
    Route::get('/dashboard', [MyController::class, 'dashboard'])->name('mymodule.dashboard');
});
```

Create controller at `Http/Controllers/MyController.php`:

```php
<?php

namespace App\Modules\MyModule\Http\Controllers;

use App\Http\Controllers\Controller;

class MyController extends Controller
{
    public function index()
    {
        return view('mymodule::index');
    }

    public function dashboard()
    {
        return view('mymodule::dashboard');
    }
}
```

### 2. Adding Database Tables

Create migration in `database/migrations/2024_01_01_000000_create_mymodule_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mymodule_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mymodule_items');
    }
};
```

The migration runs automatically when you install the module.

### 3. Adding Views

Create `resources/views/index.blade.php`:

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>My Module</h1>
    <p>Welcome to my custom module!</p>
</div>
@endsection
```

Access it via: `return view('mymodule::index');`

### 4. Creating Services

Create `Services/MyService.php`:

```php
<?php

namespace App\Modules\MyModule\Services;

class MyService
{
    public function processData($data)
    {
        // Your business logic
        return $data;
    }
}
```

Register in `Providers/MyModuleServiceProvider.php`:

```php
<?php

namespace App\Modules\MyModule\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\MyModule\Services\MyService;

class MyModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MyService::class);
    }

    public function boot(): void
    {
        // Boot services
    }
}
```

Use in controllers:

```php
public function __construct(protected MyService $myService)
{
}

public function process()
{
    $result = $this->myService->processData($data);
}
```

### 5. Using Module Hooks

Add hooks to extend functionality:

```php
class MyModuleModule extends BaseModule
{
    protected function onEnable(): void
    {
        // Register custom hooks
        $this->registerHook('after_data_save', function($data) {
            \Log::info('Data saved', ['data' => $data]);
        });
        
        // Register multiple hooks
        $this->registerHook('before_delete', function($item) {
            // Cleanup before deletion
        }, priority: 5);
    }
    
    public function saveData($data)
    {
        // Save logic here
        
        // Execute hook
        $this->executeHook('after_data_save', $data);
    }
}
```

### 6. Module Configuration

Create `config/mymodule.php`:

```php
<?php

return [
    'items_per_page' => 20,
    'cache_enabled' => true,
    'cache_ttl' => 3600,
    'features' => [
        'notifications' => true,
        'export' => true,
    ],
];
```

Use in your module:

```php
// Get config value
$perPage = $this->config('items_per_page', 10);

// Set runtime config
$this->setConfig('cache_enabled', false);

// Get all config
$allConfig = $this->getAllConfig();
```

### 7. Adding Module Dependencies

If your module depends on other modules, declare them in `module.json`:

```json
{
    "name": "AdvancedModule",
    "dependencies": ["CoreModule", "AuthModule"]
}
```

The system will automatically ensure dependencies are enabled before allowing this module to be enabled.

## Testing Your Module

Create `tests/MyModuleTest.php`:

```php
<?php

namespace Tests\Modules\MyModule;

use Tests\TestCase;
use App\Modules\MyModule\MyModuleModule;
use App\Modules\MyModule\Services\MyService;

class MyModuleTest extends TestCase
{
    public function test_module_can_be_created()
    {
        $module = new MyModuleModule();
        $this->assertInstanceOf(MyModuleModule::class, $module);
    }

    public function test_service_processes_data()
    {
        $service = app(MyService::class);
        $result = $service->processData(['test' => 'data']);
        $this->assertNotNull($result);
    }
}
```

Run tests:

```bash
php artisan test --filter MyModule
```

## Module Management Commands

```bash
# List all modules
php artisan module list

# Create new module
php artisan module create ModuleName

# Install module (migrations + enable)
php artisan module install ModuleName

# Enable module
php artisan module enable ModuleName

# Disable module
php artisan module disable ModuleName

# Uninstall module (requires confirmation)
php artisan module uninstall ModuleName

# Show module information
php artisan module info ModuleName
```

## Publishing Assets

If your module has CSS/JS/images in `resources/assets/`:

```
resources/assets/
├── css/
│   └── style.css
├── js/
│   └── script.js
└── images/
    └── logo.png
```

They will be published to `public/modules/MyModule/` during installation.

Use in Blade templates:

```blade
<link href="{{ asset('modules/MyModule/css/style.css') }}" rel="stylesheet">
<script src="{{ asset('modules/MyModule/js/script.js') }}"></script>
<img src="{{ asset('modules/MyModule/images/logo.png') }}" alt="Logo">
```

## Best Practices Summary

1. **Keep it simple** - Start with minimal functionality and expand
2. **Test early** - Write tests as you develop
3. **Document** - Add clear comments and update module.json
4. **Use hooks** - Leverage the hook system for extensibility
5. **Handle errors** - Always wrap risky operations in try-catch
6. **Follow conventions** - Use Laravel's naming and structure conventions
7. **Version properly** - Use semantic versioning
8. **Check dependencies** - Always declare module dependencies

## Example: Complete E-commerce Module

Here's a complete example of a simple product catalog module:

```php
// ProductCatalogModule.php
<?php

namespace App\Modules\ProductCatalog;

use App\Modules\BaseModule;

class ProductCatalogModule extends BaseModule
{
    protected function onEnable(): void
    {
        $this->registerHook('product_created', function($product) {
            \Log::info("Product created: {$product->name}");
        });
    }

    protected function onInstall(): void
    {
        // Seed default categories
        \DB::table('product_categories')->insert([
            ['name' => 'Electronics', 'slug' => 'electronics'],
            ['name' => 'Clothing', 'slug' => 'clothing'],
        ]);
    }
}
```

```php
// Http/Controllers/ProductController.php
<?php

namespace App\Modules\ProductCatalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ProductCatalog\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::paginate(20);
        return view('productcatalog::products.index', compact('products'));
    }

    public function show($id)
    {
        $product = Product::findOrFail($id);
        return view('productcatalog::products.show', compact('product'));
    }
}
```

## Next Steps

1. Read the full [Module Development Guide](MODULE_DEVELOPMENT.md)
2. Study the example `BlogModule` in `app/Modules/BlogModule/`
3. Explore the source code in `app/Modules/`
4. Join our community for support and examples

## Getting Help

- Check the [Module Development Guide](MODULE_DEVELOPMENT.md) for detailed documentation
- Review example modules in `app/Modules/`
- Check logs in `storage/logs/` for debugging
- Open an issue on GitHub for bugs or feature requests

Happy module development! 🚀
