<?php

use App\Models\User;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test users
    $this->user1 = User::create([
        'name' => 'Owner One',
        'email' => 'owner1@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->user2 = User::create([
        'name' => 'Owner Two',
        'email' => 'owner2@example.com',
        'password' => bcrypt('password'),
    ]);

    // Create test groups
    $this->group1 = Group::create([
        'name' => 'Laravel Community',
        'description' => 'A group for Laravel developers',
        'owner_id' => $this->user1->id,
        'type' => 'public',
    ]);

    $this->group2 = Group::create([
        'name' => 'Private Beta Testers',
        'description' => 'Private group for beta testing',
        'owner_id' => $this->user2->id,
        'type' => 'private',
    ]);

    $this->group3 = Group::create([
        'name' => 'Admin Team',
        'description' => 'Restricted admin access',
        'owner_id' => $this->user1->id,
        'type' => 'restricted',
    ]);
});

it('can search groups by name', function () {
    $response = $this->getJson('/api/search/groups?query=Laravel');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Laravel Community']);
});

it('can search groups by description', function () {
    $response = $this->getJson('/api/search/groups?query=beta');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Private Beta Testers']);
});

it('can filter groups by type', function () {
    $response = $this->getJson('/api/search/groups?type=public');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['type' => 'public']);
});

it('can filter groups by owner', function () {
    $response = $this->getJson('/api/search/groups?owner_id=' . $this->user1->id);

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
    
    $data = $response->json('data');
    expect($data)->each(fn ($group) => expect($group['owner_id'])->toBe($this->user1->id));
});

it('can filter groups by creation date range', function () {
    // Update group creation dates for testing
    $this->group1->update(['created_at' => now()->subDays(10)]);
    $this->group2->update(['created_at' => now()->subDays(5)]);
    $this->group3->update(['created_at' => now()->subDay()]);

    $from = now()->subDays(6)->toDateString();
    $to = now()->toDateString();

    $response = $this->getJson("/api/search/groups?created_from={$from}&created_to={$to}");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

it('can sort groups by name', function () {
    $response = $this->getJson('/api/search/groups?order_by=name&order_direction=asc');

    $response->assertStatus(200);
    
    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names[0])->toBe('Admin Team');
});

it('can paginate groups', function () {
    // Create more groups
    for ($i = 1; $i <= 20; $i++) {
        Group::create([
            'name' => "Group {$i}",
            'description' => "Description {$i}",
            'owner_id' => $this->user1->id,
            'type' => 'public',
        ]);
    }

    $response = $this->getJson('/api/search/groups?per_page=10');

    $response->assertStatus(200)
        ->assertJsonCount(10, 'data')
        ->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'per_page',
            'total',
        ]);
});

it('validates group search type', function () {
    $response = $this->getJson('/api/search/groups?type=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

it('validates owner_id exists', function () {
    $response = $this->getJson('/api/search/groups?owner_id=99999');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['owner_id']);
});

it('can combine multiple group filters', function () {
    $response = $this->getJson('/api/search/groups?query=admin&type=restricted&owner_id=' . $this->user1->id);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Admin Team']);
});

it('returns groups with owner relationship', function () {
    $response = $this->getJson('/api/search/groups');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'type',
                    'owner_id',
                    'owner' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
            ],
        ]);
});
