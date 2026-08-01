<?php

namespace Liberu\Foundation\ModuleManager\Console;

use Illuminate\Console\Command;
use Liberu\Foundation\ModuleManager\Cache\RegistryCache;

final class ClearModulesCommand extends Command
{
    protected $signature = 'module:clear';

    protected $description = 'Clear the deployment module registry cache';

    public function handle(RegistryCache $cache): int
    {
        $cache->clear((string) config('modules.cache_path'));
        $this->info('Module registry cache cleared.');

        return self::SUCCESS;
    }
}
