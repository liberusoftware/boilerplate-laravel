<?php

namespace Liberu\Foundation\DeveloperExperience\Console;

use Illuminate\Console\Command;
use Liberu\Foundation\DeveloperExperience\Support\EnvironmentDoctor;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use Liberu\Foundation\ModuleManager\ModuleValidator;

final class FoundationDoctorCommand extends Command
{
    protected $signature = 'foundation:doctor';

    protected $description = 'Check required extensions, writable runtime paths, and the module composition';

    public function handle(ModuleRegistry $registry, ModuleValidator $validator, EnvironmentDoctor $environment): int
    {
        $errors = $environment->inspect(
            ['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'mbstring', 'openssl', 'pdo', 'session', 'tokenizer'],
            [storage_path(), base_path('bootstrap/cache')],
        );
        $errors = array_merge($errors, $validator->validate($registry, app()->version()));
        foreach ($errors as $error) {
            $this->error($error);
        }if ($errors === []) {
            $this->info('Foundation checks passed.');
        }

        return $errors === [] ? self::SUCCESS : self::FAILURE;
    }
}
