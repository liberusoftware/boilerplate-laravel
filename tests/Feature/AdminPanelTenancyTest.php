<?php

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;

it('scopes the admin panel to the Team tenant', function () {
    expect(Filament::getPanel('admin')->getTenantModel())->toBe(Team::class);
});

it('enables shield tenancy with the team_id column present', function () {
    expect(Utils::isTenancyEnabled())->toBeTrue()
        ->and(Schema::hasColumn('roles', 'team_id'))->toBeTrue();
});

it('exposes owned and member teams as Filament tenants', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($member, ['role' => 'editor']);
    $member->forceFill(['current_team_id' => $team->id])->save();

    // A non-owner member must still see the team as a tenant, or they get locked out.
    expect($member->fresh()->getTenants(Filament::getPanel('admin'))->pluck('id'))
        ->toContain($team->id)
        ->and($member->fresh()->getDefaultTenant(Filament::getPanel('admin'))->getKey())
        ->toBe($team->id);
});

it('renders the tenant-scoped Shield RoleResource without a 500', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $owner->forceFill(['current_team_id' => $team->id])->save();

    setPermissionsTeamId($team->id);
    $owner->assignRole(Role::create(['name' => 'super_admin', 'guard_name' => 'web']));
    $editor = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    // Default shield config gates access via permissions, not a gate bypass; authorise
    // for this render so the tenant-scoped table query actually executes.
    Gate::before(fn ($user) => $user->hasRole('super_admin') ? true : null);

    $this->actingAs($owner);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($team);

    $roleResource = collect(Filament::getPanel('admin')->getResources())
        ->first(fn (string $resource) => is_a($resource::getModel(), SpatieRole::class, true));

    expect($roleResource)->not->toBeNull();

    $listPage = $roleResource::getPages()['index']->getPage();

    Livewire::test($listPage)
        ->assertOk()
        ->assertCanSeeTableRecords([$editor]);
});
