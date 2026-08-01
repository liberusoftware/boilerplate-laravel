<?php

namespace Liberu\Foundation\Identity\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Liberu\Foundation\Identity\Events\IdentityEvent;

final class EmitAuthenticationEvent
{
    public function handle(object $event): void
    {
        $name = match (true) {
            $event instanceof Login => 'identity.login.succeeded',$event instanceof Failed => 'identity.login.failed',$event instanceof Logout => 'identity.logout.completed',default => null
        };
        if ($name === null) {
            return;
        }$actor = $event->user ?? null;
        event(new IdentityEvent($name, $actor?->getAuthIdentifier(), ['guard' => (string) ($event->guard ?? 'unknown')]));
    }
}
