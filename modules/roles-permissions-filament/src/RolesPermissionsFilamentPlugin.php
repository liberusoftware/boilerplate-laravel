<?php

namespace Liberu\Foundation\RolesPermissionsFilament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;

final class RolesPermissionsFilamentPlugin extends FilamentShieldPlugin
{
    public static function make(): static
    {
        /** @var static $plugin */
        $plugin = app(self::class);

        return $plugin->scopeToTenant(false);
    }
}
