<?php

namespace Liberu\Foundation\SessionsDevicesFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Foundation\SessionsDevicesFilament\Pages\AccountSecurity;

final class SessionsDevicesFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-sessions-devices';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([AccountSecurity::class]);
    }

    public function boot(Panel $panel): void {}
}
