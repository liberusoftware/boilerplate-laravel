<?php

use App\Models\Team;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Schema;

it('scopes the admin panel to the Team tenant', function () {
    expect(Filament::getPanel('admin')->getTenantModel())->toBe(Team::class);
});

it('enables shield tenancy with the team_id column present (no 500 on role resolution)', function () {
    expect(Utils::isTenancyEnabled())->toBeTrue()
        ->and(Schema::hasColumn('roles', 'team_id'))->toBeTrue();
});
