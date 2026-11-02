<?php

declare(strict_types=1);

namespace Liberu\Foundation\Tenancy\Listeners;

use Filament\Events\TenantSet;

class SwitchTeam
{
    public function handle(object $event): void
    {
        if ($event instanceof TenantSet) {
            setPermissionsTeamId($event->getTenant()->getKey());
        }
    }
}
