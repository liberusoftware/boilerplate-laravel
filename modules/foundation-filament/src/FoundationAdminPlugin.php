<?php

namespace Liberu\Foundation\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Foundation\Filament\Pages\FoundationOperations;

final class FoundationAdminPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-foundation-admin';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([FoundationOperations::class]);
    }

    public function boot(Panel $panel): void {}
}
