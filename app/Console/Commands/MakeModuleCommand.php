<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module {name : The name of the module}
                            {--force : Overwrite existing module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module following internachi/modular pattern';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $force = $this->option('force');

        // Validate module name
        if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name)) {
            $this->error('Module name must start with a capital letter and contain only alphanumeric characters.');
            return 1;
        }

        $modulesDirectory = config('modular.modules_directory', 'app-modules');
        $modulePath = base_path("{$modulesDirectory}/{$name}");

        // Check if module already exists
        if (File::exists($modulePath) && !$force) {
            $this->error("Module {$name} already exists. Use --force to overwrite.");
            return 1;
        }

        // Create module structure
        $this->info("Creating module: {$name}");
        
        $this->createModuleStructure($modulePath, $name);
        $this->createComposerJson($modulePath, $name);
        $this->createModuleServiceProvider($modulePath, $name);
        $this->createModuleClass($modulePath, $name);
        $this->createRouteFiles($modulePath, $name);
        $this->createControllerExample($modulePath, $name);
        $this->createModelExample($modulePath, $name);
        $this->createMigrationExample($modulePath, $name);
        $this->createViewExample($modulePath, $name);
        $this->createConfigFile($modulePath, $name);
        $this->createTestExample($modulePath, $name);

        $this->info("Module {$name} created successfully!");
        $this->info("Run 'composer dump-autoload' to register the module.");
        
        return 0;
    }

    /**
     * Create the module directory structure.
     */
    protected function createModuleStructure(string $modulePath, string $name): void
    {
        $directories = [
            'src/Http/Controllers',
            'src/Http/Middleware',
            'src/Models',
            'src/Services',
            'src/Filament/Resources',
            'src/Filament/Pages',
            'src/Filament/Widgets',
            'routes',
            'database/migrations',
            'database/factories',
            'database/seeders',
            'resources/views',
            'resources/lang/en',
            'resources/assets/css',
            'resources/assets/js',
            'config',
            'tests/Unit',
            'tests/Feature',
        ];

        foreach ($directories as $directory) {
            File::makeDirectory("{$modulePath}/{$directory}", 0755, true, true);
        }
    }

    /**
     * Create composer.json for the module.
     */
    protected function createComposerJson(string $modulePath, string $name): void
    {
        $namespace = config('modular.modules_namespace', 'Modules');
        $kebabName = Str::kebab($name);

        $composer = [
            'name' => "modules/{$kebabName}",
            'description' => "{$name} Module",
            'type' => 'library',
            'autoload' => [
                'psr-4' => [
                    "{$namespace}\\{$name}\\" => 'src/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    "{$namespace}\\{$name}\\Tests\\" => 'tests/',
                ],
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [
                        "{$namespace}\\{$name}\\Providers\\{$name}ServiceProvider",
                    ],
                ],
            ],
        ];

        File::put(
            "{$modulePath}/composer.json",
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Create the module service provider.
     */
    protected function createModuleServiceProvider(string $modulePath, string $name): void
    {
        $namespace = config('modular.modules_namespace', 'Modules');
        
        $stub = <<<PHP
<?php

namespace {$namespace}\\{$name}\Providers;

use Illuminate\Support\ServiceProvider;

class {$name}ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module services
        \$this->mergeConfigFrom(
            __DIR__ . '/../../config/{$name}.php',
            strtolower('{$name}')
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        \$this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        
        // Load migrations
        \$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // Load views
        \$this->loadViewsFrom(__DIR__ . '/../../resources/views', strtolower('{$name}'));
        
        // Load translations
        \$this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', strtolower('{$name}'));

        // Publish config
        \$this->publishes([
            __DIR__ . '/../../config/{$name}.php' => config_path(strtolower('{$name}') . '.php'),
        ], '{$name}-config');

        // Publish migrations
        \$this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], '{$name}-migrations');

        // Publish views
        \$this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/' . strtolower('{$name}')),
        ], '{$name}-views');
    }
}
PHP;

        File::makeDirectory("{$modulePath}/src/Providers", 0755, true, true);
        File::put("{$modulePath}/src/Providers/{$name}ServiceProvider.php", $stub);
    }

    /**
     * Create the main module class.
     */
    protected function createModuleClass(string $modulePath, string $name): void
    {
        $namespace = config('modular.modules_namespace', 'Modules');
        
        $stub = <<<PHP
<?php

namespace {$namespace}\\{$name};

class {$name}Module
{
    /**
     * Get the module name.
     */
    public static function getName(): string
    {
        return '{$name}';
    }

    /**
     * Get the module version.
     */
    public static function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Get the module description.
     */
    public static function getDescription(): string
    {
        return '{$name} Module';
    }
}
PHP;

        File::put("{$modulePath}/src/{$name}Module.php", $stub);
    }

    /**
     * Create route files.
     */
    protected function createRouteFiles(string $modulePath, string $name): void
    {
        $kebabName = Str::kebab($name);
        $namespace = config('modular.modules_namespace', 'Modules');

        // Web routes
        $webRoutes = <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use {$namespace}\\{$name}\Http\Controllers\\{$name}Controller;

Route::middleware(['web'])->group(function () {
    Route::get('/{$kebabName}', [{$name}Controller::class, 'index'])->name('{$kebabName}.index');
});
PHP;

        File::put("{$modulePath}/routes/web.php", $webRoutes);

        // API routes
        $apiRoutes = <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use {$namespace}\\{$name}\Http\Controllers\\{$name}Controller;

Route::middleware(['api'])->prefix('api')->group(function () {
    Route::get('/{$kebabName}', [{$name}Controller::class, 'index']);
});
PHP;

        File::put("{$modulePath}/routes/api.php", $apiRoutes);
    }

    /**
     * Create a controller example.
     */
    protected function createControllerExample(string $modulePath, string $name): void
    {
        $namespace = config('modular.modules_namespace', 'Modules');
        
        $stub = <<<PHP
<?php

namespace {$namespace}\\{$name}\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class {$name}Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('{$name}::index');
    }
}
PHP;

        File::put("{$modulePath}/src/Http/Controllers/{$name}Controller.php", $stub);
    }

    /**
     * Create a model example.
     */
    protected function createModelExample(string $modulePath, string $name): void
    {
        $namespace = config('modular.modules_namespace', 'Modules');
        
        $stub = <<<PHP
<?php

namespace {$namespace}\\{$name}\Models;

use Illuminate\Database\Eloquent\Model;

class {$name} extends Model
{
    protected \$fillable = [];
}
PHP;

        File::put("{$modulePath}/src/Models/{$name}.php", $stub);
    }

    /**
     * Create a migration example.
     */
    protected function createMigrationExample(string $modulePath, string $name): void
    {
        $table = Str::snake(Str::plural($name));
        $timestamp = date('Y_m_d_His');
        
        $stub = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};
PHP;

        File::put("{$modulePath}/database/migrations/{$timestamp}_create_{$table}_table.php", $stub);
    }

    /**
     * Create a view example.
     */
    protected function createViewExample(string $modulePath, string $name): void
    {
        $stub = <<<BLADE
<!DOCTYPE html>
<html>
<head>
    <title>{$name} Module</title>
</head>
<body>
    <h1>Welcome to {$name} Module</h1>
</body>
</html>
BLADE;

        File::put("{$modulePath}/resources/views/index.blade.php", $stub);
    }

    /**
     * Create a config file.
     */
    protected function createConfigFile(string $modulePath, string $name): void
    {
        $stub = <<<PHP
<?php

return [
    'enabled' => true,
    'version' => '1.0.0',
];
PHP;

        File::put("{$modulePath}/config/{$name}.php", $stub);
    }

    /**
     * Create a test example.
     */
    protected function createTestExample(string $modulePath, string $name): void
    {
        $namespace = config('modular.modules_namespace', 'Modules');
        
        $stub = <<<PHP
<?php

namespace {$namespace}\\{$name}\Tests\Feature;

use Tests\TestCase;

class {$name}Test extends TestCase
{
    public function test_module_index(): void
    {
        \$response = \$this->get(route('{$name}.index'));
        \$response->assertStatus(200);
    }
}
PHP;

        File::put("{$modulePath}/tests/Feature/{$name}Test.php", $stub);
    }
}
