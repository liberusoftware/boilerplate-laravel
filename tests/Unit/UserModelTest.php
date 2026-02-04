<?php

use App\Models\User;

it('returns the profile_photo_url when profile_photo_path is a URL', function () {
    $u = new User();
    $u->profile_photo_path = 'https://example.test/avatar.png';

    $attr = $u->profilePhotoUrl()->get();
    expect($attr)->toBe('https://example.test/avatar.png');
});

it('can access panel/filament/tenant checks return booleans', function () {
    $u = new User();
    expect($u->canAccessPanel(null))->toBeTrue();
    expect($u->canAccessFilament())->toBeTrue();
    // passing a dummy model for tenant check
    $dummy = new class extends \Illuminate\Database\Eloquent\Model {};
    expect($u->canAccessTenant($dummy))->toBeTrue();
});
