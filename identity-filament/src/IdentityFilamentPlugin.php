<?php

namespace Liberu\Foundation\IdentityFilament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Foundation\IdentityFilament\Resources\UserResource;

final class IdentityFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-identity';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([UserResource::class]);
    }

    public function boot(Panel $panel): void {}
}
