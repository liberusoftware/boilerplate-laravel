<?php

namespace Liberu\Foundation\ModuleManager\Console;

use Illuminate\Console\Command;
use Liberu\Foundation\ModuleManager\ModuleRegistry;

final class ListModulesCommand extends Command
{
    protected $signature = 'module:list';

    protected $description = 'List discovered modules and deployment enablement state';

    public function handle(ModuleRegistry $registry): int
    {
        $enabled = collect($registry->resolve((array) config('modules.enabled', []), (array) config('modules.disabled', [])))
            ->keyBy(fn ($manifest) => $manifest->name());

        $this->table(['Module', 'Version', 'Category', 'Enabled', 'Capabilities', 'Features'], array_map(
            fn ($manifest): array => [
                $manifest->name(),
                $manifest->version(),
                $manifest->category(),
                $enabled->has($manifest->name()) ? 'yes' : 'no',
                implode(', ', $manifest->capabilities()),
                count($manifest->features()),
            ],
            $registry->all(),
        ));

        return self::SUCCESS;
    }
}
