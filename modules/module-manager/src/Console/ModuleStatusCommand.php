<?php

namespace Liberu\Foundation\ModuleManager\Console;

use Illuminate\Console\Command;
use Liberu\Foundation\ModuleManager\ModuleRegistry;

final class ModuleStatusCommand extends Command
{
    protected $signature = 'module:status {name}';

    protected $description = 'Inspect one installed module, its deployment state, dependencies, capabilities, and features';

    public function handle(ModuleRegistry $registry): int
    {
        $name = (string) $this->argument('name');
        $manifest = $registry->get($name);
        if ($manifest === null) {
            $this->error("Module [{$name}] is not installed.");

            return self::FAILURE;
        }

        $resolved = collect($registry->resolve((array) config('modules.enabled'), (array) config('modules.disabled')))->contains(fn ($item) => $item->name() === $name);
        $this->table(['Property', 'Value'], [
            ['installed', 'yes'],
            ['enabled', $resolved ? 'yes' : 'no'],
            ['version', $manifest->version()],
            ['provider', $manifest->provider()],
            ['dependencies', json_encode($manifest->requiredPackages(), JSON_THROW_ON_ERROR)],
            ['capabilities', implode(', ', $manifest->capabilities())],
            ['features', implode(PHP_EOL, $manifest->features())],
            ['path', $manifest->path],
        ]);

        return self::SUCCESS;
    }
}
