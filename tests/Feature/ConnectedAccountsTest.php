<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// The connected-accounts profile view calls ->map()/->where() on
// $user->connectedAccounts; without the Socialstream relationship it is null.
it('exposes the connectedAccounts relationship', function () {
    $user = User::factory()->create();

    expect($user->connectedAccounts)->toBeInstanceOf(Collection::class)
        ->and($user->connectedAccounts)->toHaveCount(0);
});
