<?php

namespace Liberu\Foundation\ModuleManager;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\ModuleManager\Cache\RegistryCache;
use Liberu\Foundation\ModuleManager\Console\CacheModulesCommand;
use Liberu\Foundation\ModuleManager\Console\ClearModulesCommand;
use Liberu\Foundation\ModuleManager\Console\ListModulesCommand;
use Liberu\Foundation\ModuleManager\Console\ModuleStatusCommand;
use Liberu\Foundation\ModuleManager\Console\ValidateModulesCommand;

final class ModuleManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/modules.php', 'modules');
        $this->app->singleton(RegistryCache::class);
        $this->app->singleton(ModuleRegistry::class, fn () => $this->app->make(RegistryCache::class)->load(
            (array) config('modules.paths', [base_path('modules')]),
            (bool) config('modules.cache', false),
            (string) config('modules.cache_path'),
        ));

        $errors = (new ModuleValidator())->validate($this->app->make(ModuleRegistry::class), Application::VERSION);
        if ($errors !== []) {
            throw new \RuntimeException("Module validation failed:\n- ".implode("\n- ", $errors));
        }

        foreach ($this->app->make(ModuleRegistry::class)->resolve(config('modules.enabled', []), config('modules.disabled', [])) as $module) {
            if ($module->name() !== 'module-manager') {
                $this->app->register($module->provider());
            }
        }
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/modules.php' => config_path('modules.php')], 'modules-config');

        if ($this->app->runningInConsole()) {
            $this->commands([CacheModulesCommand::class, ClearModulesCommand::class, ListModulesCommand::class, ModuleStatusCommand::class, ValidateModulesCommand::class]);
        }
    }
}
