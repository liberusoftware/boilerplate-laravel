<?php

namespace Liberu\Blog\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Blog\Filament\Resources\PostResource;

final class BlogFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-blog';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([PostResource::class]);
    }

    public function boot(Panel $panel): void {}
}
