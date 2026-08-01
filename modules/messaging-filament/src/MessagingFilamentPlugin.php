<?php

namespace Liberu\Messaging\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Liberu\Messaging\Filament\Pages\Inbox;

final class MessagingFilamentPlugin implements Plugin
{
    public static function make(): self
    {
        return new self();
    }

    public function getId(): string
    {
        return 'liberu-messaging';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([Inbox::class]);
    }

    public function boot(Panel $panel): void {}
}
