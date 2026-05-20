<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ModuleAutoloadCommand extends Command
{
    protected $signature = 'module:dump-autoload';
    protected $description = 'Run composer dump-autoload to refresh module autoload mapping.';

    public function handle(): int
    {
        $this->info('Running composer dump-autoload...');

        // Attempt to run composer in project root.
        $composer = base_path('composer.phar');

        // Prefer local composer.phar if present, otherwise call `composer` directly
        if (file_exists($composer)) {
            $cmd = escapeshellcmd(PHP_BINARY . ' ' . $composer . ' dump-autoload');
        } else {
            $cmd = 'composer dump-autoload';
        }

        $this->line("Executing: {$cmd}");

        $output = null;
        $returnVar = null;
        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->error('composer dump-autoload failed.');
            return 1;
        }

        $this->info('composer dump-autoload completed.');
        return 0;
    }
}
