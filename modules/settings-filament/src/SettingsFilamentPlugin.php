<?php

namespace Liberu\Foundation\SettingsFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Foundation\SettingsFilament\Pages\ManageSiteSettings;

final class SettingsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-settings';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([ManageSiteSettings::class]);
    }

    public function boot(Panel $panel): void {}
}
