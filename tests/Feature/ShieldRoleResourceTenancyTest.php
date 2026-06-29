<?php

use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;

it('does not tenant-scope the shield role resource when teams are disabled', function () {
    // permission.teams is off in this app, so roles are global. If the resource
    // stays tenant-scoped, Filament tries Role->team() and 500s the admin panel.
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(Utils::isTenancyEnabled())->toBeFalse()
        ->and(RoleResource::isScopedToTenant())->toBeFalse();
});
