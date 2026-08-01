<?php

namespace Liberu\Foundation\ModuleManager\Console;

use Illuminate\Console\Command;
use Liberu\Foundation\ModuleManager\Cache\RegistryCache;
use Liberu\Foundation\ModuleManager\ModuleDiscovery;

final class CacheModulesCommand extends Command
{
    protected $signature = 'module:cache';

    protected $description = 'Validate and atomically cache the discovered module registry for deployment';

    public function handle(RegistryCache $cache): int
    {
        $registry = (new ModuleDiscovery())->discover((array) config('modules.paths'));
        $cache->write($registry, (string) config('modules.cache_path'));
        $this->info('Module registry cached.');

        return self::SUCCESS;
    }
}
