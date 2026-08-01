<?php

namespace Liberu\Foundation\OrganizationsFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Foundation\OrganizationsFilament\Resources\TeamResource;

final class OrganizationsFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-organizations';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([TeamResource::class]);
    }

    public function boot(Panel $panel): void {}
}
