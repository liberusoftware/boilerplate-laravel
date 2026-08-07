<?php

use Liberu\Foundation\IdentityFilament\Resources\UserResource;
use Liberu\Foundation\IdentityFilament\Resources\UserResource\Pages\ListUsers;
use Liberu\Foundation\IdentityFilament\Tests\Fixtures\RoledUser;
use Livewire\Livewire;

/**
 * Records are created rather than asserting `assertOk()` on an empty page: an
 * empty table renders successfully whatever is wrong with its columns, so a test
 * without rows cannot fail on the thing it is named for.
 */
it('renders the user table with its columns resolved', function () {
    $actor = RoledUser::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.test']);
    RoledUser::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.test']);

    $this->actingAs($actor);

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertSee('Ada Lovelace')
        ->assertSee('grace@example.test');
});

it('is not tenant-scoped', function () {
    // The host's admin panel is tenant-scoped to a Team, and this model has no
    // team() relation — owned and member teams both, no single belongsTo. Without
    // the override the tenant panel 500s on this resource.
    expect(UserResource::isScopedToTenant())->toBeFalse();
});
