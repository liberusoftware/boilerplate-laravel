<?php

namespace Liberu\Foundation\SessionsDevicesFilament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Liberu\Foundation\Sessions\Queries\SessionReader;

final class AccountSecurity extends Page
{
    protected string $view = 'sessions-devices-filament::pages.account-security';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Security & Preferences';

    protected static string|\UnitEnum|null $navigationGroup = 'Account';

    public Collection $sessions;

    public function mount(SessionReader $reader): void
    {
        $this->sessions = $reader->forActor(auth()->id(), session()->getId());
    }

    public function revoke(string $sessionId, SessionReader $reader): void
    {
        $reader->revoke(auth()->id(), $sessionId, session()->getId());
        $this->sessions = $reader->forActor(auth()->id(), session()->getId());
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}
