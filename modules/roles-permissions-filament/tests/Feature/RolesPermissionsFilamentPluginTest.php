<?php

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Liberu\Foundation\RolesPermissionsFilament\RolesPermissionsFilamentPlugin;

/**
 * This package exists to wrap Shield with one setting changed, so that setting is
 * the whole of its behaviour and the only thing worth asserting.
 *
 * Tenant resource scoping is off deliberately: `permission.teams` is true, so
 * Spatie already isolates roles by `team_id`, and letting Shield scope the
 * resources as well would filter them a second time by a relationship the role
 * model does not have.
 */
it('disables tenant resource scoping', function () {
    expect(RolesPermissionsFilamentPlugin::make()->isScopedToTenant())->toBeFalse();
});

it('is a Shield plugin rather than a reimplementation', function () {
    // The host composes panels from whatever a manifest declares and rejects
    // anything that is not a Filament plugin, so the inheritance is load-bearing.
    expect(RolesPermissionsFilamentPlugin::make())->toBeInstanceOf(FilamentShieldPlugin::class);
});

it('keeps Shield\'s plugin id, so a panel cannot register both', function () {
    expect(RolesPermissionsFilamentPlugin::make()->getId())
        ->toBe(FilamentShieldPlugin::make()->getId());
});
