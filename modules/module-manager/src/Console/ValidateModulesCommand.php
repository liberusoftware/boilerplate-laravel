<?php

namespace Liberu\Foundation\ModuleManager\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Application;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use Liberu\Foundation\ModuleManager\ModuleValidator;

final class ValidateModulesCommand extends Command
{
    protected $signature = 'module:validate';

    protected $description = 'Validate module manifests, Composer metadata, providers, compatibility, and dependency ordering';

    public function handle(ModuleRegistry $registry, ModuleValidator $validator): int
    {
        $errors = $validator->validate($registry, Application::VERSION);

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info('All discovered modules are valid.');

        return self::SUCCESS;
    }
}
