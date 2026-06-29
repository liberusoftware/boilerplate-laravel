<?php

use App\Models\Team;
use App\Models\User;

it('lets an owner update and delete their team', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);

    expect($owner->can('update', $team))->toBeTrue()
        ->and($owner->can('delete', $team))->toBeTrue()
        ->and($owner->can('addTeamMember', $team))->toBeTrue();
});

it('denies a stranger updating or deleting a team', function () {
    $stranger = User::factory()->create();
    $team = Team::factory()->create(['user_id' => User::factory()->create()->id]);

    expect($stranger->can('update', $team))->toBeFalse()
        ->and($stranger->can('delete', $team))->toBeFalse();
});

it('allows viewing only for team members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $stranger = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($member, ['role' => 'editor']);

    expect($owner->can('view', $team))->toBeTrue()
        ->and($member->fresh()->can('view', $team))->toBeTrue()
        ->and($stranger->can('view', $team))->toBeFalse();
});
