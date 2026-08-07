<?php

use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Foundation\OrganizationsFilament\Resources\TeamResource\Pages\ListTeams;
use Liberu\Foundation\OrganizationsFilament\Tests\Fixtures\OrganizationUser;
use Livewire\Livewire;

/**
 * A record is created rather than asserting `assertOk()` on an empty page: an
 * empty table renders successfully whatever is wrong with its columns, so a test
 * without rows cannot fail on the thing it is named for. `owner.name` in
 * particular only resolves if the relation and the configured user model agree.
 */
it('renders the team table with its columns resolved', function () {
    // Created directly rather than through the factory: the testbench's factory
    // is bound to TestUser, so OrganizationUser::factory() still returns a
    // TestUser — and TeamPolicy type-hints the contract only this subclass has.
    $owner = OrganizationUser::forceCreate([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.test',
        'password' => bcrypt('secret'),
    ]);

    Team::forceCreate([
        'user_id' => $owner->id,
        'name' => 'Analytical Engines',
        'personal_team' => false,
    ]);

    $this->actingAs($owner);

    Livewire::test(ListTeams::class)
        ->assertOk()
        ->assertSee('Analytical Engines')
        ->assertSee('Ada Lovelace');
});
