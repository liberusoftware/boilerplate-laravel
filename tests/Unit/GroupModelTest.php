<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has fillable attributes', function () {
    $group = new Group;
    expect($group->getFillable())->toContain('name', 'description', 'owner_id', 'type');
});

it('casts is_active to boolean', function () {
    $casts = (new Group)->getCasts();
    expect($casts)->toHaveKey('is_active');
    expect($casts['is_active'])->toBe('boolean');
});

it('owner relationship resolves to user', function () {
    $owner = User::factory()->create();
    $group = Group::create(['name' => 'Test', 'owner_id' => $owner->id, 'type' => 'public']);

    expect($group->owner)->toBeInstanceOf(User::class);
    expect($group->owner->id)->toBe($owner->id);
});

it('scopeSearch filters by name', function () {
    $owner = User::factory()->create();
    Group::create(['name' => 'Laravel Devs', 'owner_id' => $owner->id, 'type' => 'public']);
    Group::create(['name' => 'PHP Community', 'owner_id' => $owner->id, 'type' => 'public']);

    $results = Group::search('Laravel')->get();
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Laravel Devs');
});

it('scopeSearch filters by description', function () {
    $owner = User::factory()->create();
    Group::create(['name' => 'Group A', 'description' => 'For React developers', 'owner_id' => $owner->id, 'type' => 'public']);
    Group::create(['name' => 'Group B', 'description' => 'For Vue developers', 'owner_id' => $owner->id, 'type' => 'public']);

    $results = Group::search('React')->get();
    expect($results)->toHaveCount(1);
});

it('scopeActive only returns active groups', function () {
    $owner = User::factory()->create();
    Group::create(['name' => 'Active', 'owner_id' => $owner->id, 'type' => 'public', 'is_active' => true]);
    Group::create(['name' => 'Inactive', 'owner_id' => $owner->id, 'type' => 'public', 'is_active' => false]);

    $active = Group::active()->get();
    expect($active->every(fn ($g) => $g->is_active === true))->toBeTrue();
    expect($active)->toHaveCount(1);
});

it('scopeType filters by type', function () {
    $owner = User::factory()->create();
    Group::create(['name' => 'Public Group', 'owner_id' => $owner->id, 'type' => 'public']);
    Group::create(['name' => 'Private Group', 'owner_id' => $owner->id, 'type' => 'private']);

    $public = Group::type('public')->get();
    expect($public)->toHaveCount(1);
    expect($public->first()->type)->toBe('public');
});

it('scopeByOwner filters by owner id', function () {
    $owner1 = User::factory()->create();
    $owner2 = User::factory()->create();
    Group::create(['name' => 'Owner1 Group', 'owner_id' => $owner1->id, 'type' => 'public']);
    Group::create(['name' => 'Owner2 Group', 'owner_id' => $owner2->id, 'type' => 'public']);

    $groups = Group::byOwner($owner1->id)->get();
    expect($groups)->toHaveCount(1);
    expect($groups->first()->owner_id)->toBe($owner1->id);
});
