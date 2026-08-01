<?php

namespace Liberu\Foundation\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Foundation\Filament\Pages\AccountSecurity;

final class FoundationAccountPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-foundation-account';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([AccountSecurity::class]);
    }

    public function boot(Panel $panel): void {}
}
