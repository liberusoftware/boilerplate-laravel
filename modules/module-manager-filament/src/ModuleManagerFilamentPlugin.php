<?php

namespace Liberu\Foundation\ModuleManagerFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Foundation\ModuleManagerFilament\Pages\FoundationOperations;

final class ModuleManagerFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-module-manager';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([FoundationOperations::class]);
    }

    public function boot(Panel $panel): void {}
}
