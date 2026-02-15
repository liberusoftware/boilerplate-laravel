<?php

namespace App\Console\Commands;

use Exception;
use App\Modules\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ModuleCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'module {action} {name?} {--force} {--format=text}';

    /**
     * The console command description.
     */
    protected $description = 'Manage application modules';

    protected ModuleManager $moduleManager;

    public function __construct(ModuleManager $moduleManager)
    {
        parent::__construct();
        $this->moduleManager = $moduleManager;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $name = $this->argument('name');

        return match ($action) {
            'list' => $this->listModules(),
            'enable' => $this->enableModule($name),
            'disable' => $this->disableModule($name),
            'install' => $this->installModule($name),
            'uninstall' => $this->uninstallModule($name),
            'create' => $this->createModule($name),
            'info' => $this->showModuleInfo($name),
            default => $this->showHelp(),
        };
    }

    /**
     * List all modules.
     */
    protected function listModules(): int
    {
        $modules = $this->moduleManager->all();

        if ($modules->isEmpty()) {
            if ($this->option('format') === 'json') {
                $this->line(json_encode(['modules' => []]));
            } else {
                $this->info('No modules found.');
            }
            return 0;
        }

        $moduleData = $modules->map(function ($module) {
            return [
                'name' => $module->getName(),
                'version' => $module->getVersion(),
                'enabled' => $module->isEnabled(),
                'description' => $module->getDescription(),
            ];
        })->toArray();

        if ($this->option('format') === 'json') {
            $this->line(json_encode(['modules' => $moduleData], JSON_PRETTY_PRINT));
        } else {
            $this->table(
                ['Name', 'Version', 'Status', 'Description'],
                array_map(function ($module) {
                    return [
                        $module['name'],
                        $module['version'],
                        $module['enabled'] ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>',
                        $module['description'],
                    ];
                }, $moduleData)
            );
        }

        return 0;
    }

    /**
     * Enable a module.
     */
    protected function enableModule(?string $name): int
    {
        if (!$name) {
            $this->error('Module name is required.');
            return 1;
        }

        try {
            $module = $this->moduleManager->find($name);
            if (!$module) {
                $this->error("Module '{$name}' not found.");
                return 1;
            }

            if ($module->isEnabled()) {
                $this->info("Module '{$name}' is already enabled.");
                return 0;
            }

            if ($this->moduleManager->enable($name)) {
                $this->info("Module '{$name}' has been enabled.");
                $this->comment("Run 'php artisan config:clear' and 'php artisan route:clear' to apply changes.");
                return 0;
            }

            $this->error("Failed to enable module '{$name}'.");
            return 1;
        } catch (Exception $e) {
            $this->error("Failed to enable module '{$name}': " . $e->getMessage());
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }
            return 1;
        }
    }

    /**
     * Disable a module.
     */
    protected function disableModule(?string $name): int
    {
        if (!$name) {
            $this->error('Module name is required.');
            return 1;
        }

        try {
            $module = $this->moduleManager->find($name);
            if (!$module) {
                $this->error("Module '{$name}' not found.");
                return 1;
            }

            if (!$module->isEnabled()) {
                $this->info("Module '{$name}' is already disabled.");
                return 0;
            }

            if ($this->moduleManager->disable($name)) {
                $this->info("Module '{$name}' has been disabled.");
                $this->comment("Run 'php artisan config:clear' and 'php artisan route:clear' to apply changes.");
                return 0;
            }

            $this->error("Failed to disable module '{$name}'.");
            return 1;
        } catch (Exception $e) {
            $this->error("Failed to disable module '{$name}': " . $e->getMessage());
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }
            return 1;
        }
    }

    /**
     * Install a module.
     */
    protected function installModule(?string $name): int
    {
        if (!$name) {
            $this->error('Module name is required.');
            return 1;
        }

        try {
            $module = $this->moduleManager->find($name);
            if (!$module) {
                $this->error("Module '{$name}' not found.");
                return 1;
            }

            if ($module->isEnabled()) {
                $this->info("Module '{$name}' is already installed and enabled.");
                return 0;
            }

            $this->info("Installing module '{$name}'...");
            
            if ($this->moduleManager->install($name)) {
                $this->info("Module '{$name}' has been installed and enabled.");
                $this->comment("Run 'php artisan config:clear' and 'php artisan route:clear' to apply changes.");
                return 0;
            }

            $this->error("Failed to install module '{$name}'.");
            return 1;
        } catch (Exception $e) {
            $this->error("Failed to install module '{$name}': " . $e->getMessage());
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }
            return 1;
        }
    }

    /**
     * Uninstall a module.
     */
    protected function uninstallModule(?string $name): int
    {
        if (!$name) {
            $this->error('Module name is required.');
            return 1;
        }

        if (!$this->option('force')) {
            if (!$this->confirm("Are you sure you want to uninstall module '{$name}'? This action cannot be undone.")) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        try {
            if ($this->moduleManager->uninstall($name)) {
                $this->info("Module '{$name}' has been uninstalled.");
                return 0;
            }

            $this->error("Module '{$name}' not found.");
            return 1;
        } catch (Exception $e) {
            $this->error("Failed to uninstall module '{$name}': " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Create a new module.
     */
    protected function createModule(?string $name): int
    {
        if (!$name) {
            $this->error('Module name is required.');
            return 1;
        }

        $modulePath = app_path("Modules/{$name}");

        if (File::exists($modulePath)) {
            $this->error("Module '{$name}' already exists.");
            return 1;
        }

        $this->createModuleStructure($name, $modulePath);
        $this->info("Module '{$name}' has been created successfully.");

        return 0;
    }

    /**
     * Show module information.
     */
    protected function showModuleInfo(?string $name): int
    {
        if (!$name) {
            $this->error('Module name is required.');
            return 1;
        }

        $info = $this->moduleManager->getModuleInfo($name);

        if (empty($info)) {
            $this->error("Module '{$name}' not found.");
            return 1;
        }

        $this->info("Module Information:");
        $this->line("Name: {$info['name']}");
        $this->line("Version: {$info['version']}");
        $this->line("Description: {$info['description']}");
        $this->line("Status: " . ($info['enabled'] ? 'Enabled' : 'Disabled'));
        
        if (!empty($info['dependencies'])) {
            $this->line("Dependencies: " . implode(', ', $info['dependencies']));
        }

        return 0;
    }

    /**
     * Create module directory structure.
     */
    protected function createModuleStructure(string $name, string $modulePath): void
    {
        // Create directories
        $directories = [
            'Providers',
            'Http/Controllers',
            'Http/Middleware',
            'Models',
            'Services',
            'resources/views',
            'resources/lang',
            'resources/assets',
            'routes',
            'database/migrations',
            'database/seeders',
            'config',
            'tests',
        ];

        foreach ($directories as $directory) {
            File::makeDirectory("{$modulePath}/{$directory}", 0755, true);
        }

        // Create module.json
        $moduleInfo = [
            'name' => $name,
            'version' => '1.0.0',
            'description' => "Custom {$name} module",
            'dependencies' => [],
            'config' => [],
        ];

        File::put("{$modulePath}/module.json", json_encode($moduleInfo, JSON_PRETTY_PRINT));

        // Create module class
        $moduleClass = $this->getModuleClassStub($name);
        File::put("{$modulePath}/{$name}Module.php", $moduleClass);

        // Create service provider
        $serviceProvider = $this->getServiceProviderStub($name);
        File::put("{$modulePath}/Providers/{$name}ServiceProvider.php", $serviceProvider);

        // Create routes files
        File::put("{$modulePath}/routes/web.php", "<?php\n\n// Web routes for {$name} module\n");
        File::put("{$modulePath}/routes/api.php", "<?php\n\n// API routes for {$name} module\n");
    }

    /**
     * Get module class stub.
     */
    protected function getModuleClassStub(string $name): string
    {
        return "<?php

namespace App\\Modules\\{$name};

use App\\Modules\\BaseModule;

class {$name}Module extends BaseModule
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
";
    }

    /**
     * Get service provider stub.
     */
    protected function getServiceProviderStub(string $name): string
    {
        return "<?php

namespace App\\Modules\\{$name}\\Providers;

use Illuminate\\Support\\ServiceProvider;

class {$name}ServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register module services
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Boot module services
    }
}
";
    }

    /**
     * Show help information.
     */
    protected function showHelp(): int
    {
        $this->info('Available actions:');
        $this->line('  list                 List all modules');
        $this->line('  enable <name>        Enable a module');
        $this->line('  disable <name>       Disable a module');
        $this->line('  install <name>       Install a module');
        $this->line('  uninstall <name>     Uninstall a module');
        $this->line('  create <name>        Create a new module');
        $this->line('  info <name>          Show module information');

        return 0;
    }
}