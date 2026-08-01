<?php

namespace Liberu\Messaging\Filament\Pages;

use Filament\Pages\Page;
use Liberu\Messaging\Core\Contracts\Messaging;

final class Inbox extends Page
{
    protected string $view = 'messaging-filament::pages.inbox';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Messages';

    protected static string|\UnitEnum|null $navigationGroup = 'Communication';

    /** @var list<array<string, mixed>> */
    public array $conversations = [];

    public function mount(Messaging $messaging): void
    {
        $this->conversations = $messaging->conversations(auth()->id());
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}
