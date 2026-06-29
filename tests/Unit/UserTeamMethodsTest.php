<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('ownsTeam is true only for owned teams', function () {
    $user = User::factory()->create();
    $owned = Team::factory()->create(['user_id' => $user->id]);
    $other = Team::factory()->create();

    expect($user->ownsTeam($owned))->toBeTrue()
        ->and($user->ownsTeam($other))->toBeFalse()
        ->and($user->ownsTeam(null))->toBeFalse();
});

it('belongsToTeam covers owned and member teams', function () {
    $user = User::factory()->create();
    $owned = Team::factory()->create(['user_id' => $user->id]);
    $member = Team::factory()->create();
    $member->users()->attach($user, ['role' => 'editor']);
    $stranger = Team::factory()->create();

    expect($user->belongsToTeam($owned))->toBeTrue()
        ->and($user->fresh()->belongsToTeam($member))->toBeTrue()
        ->and($user->belongsToTeam($stranger))->toBeFalse();
});

it('switchTeam persists current team for members and rejects others', function () {
    $user = User::factory()->create();
    $owned = Team::factory()->create(['user_id' => $user->id]);
    $stranger = Team::factory()->create();

    expect($user->switchTeam($owned))->toBeTrue()
        ->and($user->fresh()->current_team_id)->toBe($owned->id)
        ->and($user->switchTeam($stranger))->toBeFalse();
});
