<?php

namespace Liberu\Foundation\AuthorizationFilament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;

final class AuthorizationFilamentPlugin extends FilamentShieldPlugin
{
    public static function make(): static
    {
        /** @var static $plugin */
        $plugin = app(self::class);

        return $plugin->scopeToTenant(false);
    }
}
