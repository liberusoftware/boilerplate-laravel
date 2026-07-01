<?php

use App\Models\Team;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('logs user changes but never the password', function () {
    $user = User::factory()->create();

    $user->update(['name' => 'Renamed Person', 'password' => bcrypt('new-secret')]);

    $activity = Activity::query()
        ->where('subject_type', $user->getMorphClass())
        ->where('subject_id', $user->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();

    // v5 stores the attribute diff in `attribute_changes`, not `properties`.
    $changed = $activity->attribute_changes->get('attributes', []);
    expect($changed)->toHaveKey('name', 'Renamed Person');
    expect($changed)->not->toHaveKey('password');
    expect($changed)->not->toHaveKey('remember_token');
});

it('logs team changes', function () {
    $user = User::factory()->create();
    $team = Team::forceCreate([
        'user_id' => $user->id,
        'name' => 'Original',
        'personal_team' => false,
    ]);

    $team->update(['name' => 'Updated Team']);

    $logged = Activity::query()
        ->where('subject_type', $team->getMorphClass())
        ->where('subject_id', $team->id)
        ->where('event', 'updated')
        ->exists();

    expect($logged)->toBeTrue();
});
