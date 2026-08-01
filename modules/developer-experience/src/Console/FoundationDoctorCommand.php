<?php

namespace Liberu\Foundation\DeveloperExperience\Console;

use Illuminate\Console\Command;
use Liberu\Foundation\ModuleManager\ModuleRegistry;
use Liberu\Foundation\ModuleManager\ModuleValidator;

final class FoundationDoctorCommand extends Command
{
    protected $signature = 'foundation:doctor';

    protected $description = 'Check required extensions, writable runtime paths, and the module composition';

    public function handle(ModuleRegistry $registry, ModuleValidator $validator): int
    {
        $errors = [];
        foreach (['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'mbstring', 'openssl', 'pdo', 'session', 'tokenizer'] as $extension) {
            if (! extension_loaded($extension)) {
                $errors[] = "Missing PHP extension: {$extension}";
            }
        }foreach ([storage_path(), base_path('bootstrap/cache')] as $path) {
            if (! is_writable($path)) {
                $errors[] = "Not writable: {$path}";
            }
        }$errors = array_merge($errors, $validator->validate($registry, app()->version()));
        foreach ($errors as $error) {
            $this->error($error);
        }if ($errors === []) {
            $this->info('Foundation checks passed.');
        }

return $errors === [] ? self::SUCCESS : self::FAILURE;
    }
}
