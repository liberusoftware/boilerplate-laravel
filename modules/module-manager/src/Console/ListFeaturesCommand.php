<?php

namespace Liberu\Foundation\ModuleManager\Console;

use Illuminate\Console\Command;
use Liberu\Foundation\ModuleManager\ModuleRegistry;

final class ListFeaturesCommand extends Command
{
    protected $signature = 'module:features {query? : Optional case-insensitive feature search}';

    protected $description = 'List the features declared by installed modules';

    public function handle(ModuleRegistry $registry): int
    {
        $rows = [];
        foreach ($registry->searchFeatures((string) ($this->argument('query') ?? '')) as $module => $features) {
            foreach ($features as $feature) {
                $rows[] = [$module, $feature];
            }
        }

        $this->table(['Module', 'Feature'], $rows);

        return self::SUCCESS;
    }
}
