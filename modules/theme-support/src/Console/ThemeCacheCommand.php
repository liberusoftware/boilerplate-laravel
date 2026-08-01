<?php

namespace Liberu\Foundation\Theme\Console;

use Illuminate\Console\Command;
use Liberu\Foundation\Theme\Cache\ThemeCache;
use Liberu\Foundation\Theme\Discovery\ThemeDiscovery;

final class ThemeCacheCommand extends Command
{
    protected $signature = 'theme:cache';

    protected $description = 'Atomically cache the validated theme registry for deployment';

    public function handle(ThemeCache $cache, ThemeDiscovery $discovery): int
    {
        $cache->write($discovery->discover(base_path('themes')), (string) config('theme.cache_path'));
        $this->info('Theme registry cached.');

        return self::SUCCESS;
    }
}
