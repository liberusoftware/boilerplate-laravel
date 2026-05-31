<?php

use App\Models\User;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;

it('returns the profile_photo_url when profile_photo_path is a URL', function () {
    $u = new User;
    $u->profile_photo_path = 'https://example.test/avatar.png';

    expect($u->profile_photo_url)->toBe('https://example.test/avatar.png');
});

it('can access panel/filament/tenant checks return booleans', function () {
    $u = new User;
    $panel = Mockery::mock(Panel::class);
    expect($u->canAccessPanel($panel))->toBeTrue();
    expect($u->canAccessFilament())->toBeTrue();
    // passing a dummy model for tenant check
    $dummy = new class extends Model {};
    expect($u->canAccessTenant($dummy))->toBeTrue();
});
