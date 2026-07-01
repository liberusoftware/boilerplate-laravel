<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test users
    $this->user1 = User::forceCreate([
        'name' => 'Alice Johnson',
        'email' => 'alice@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->user2 = User::forceCreate([
        'name' => 'Bob Wilson',
        'email' => 'bob@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => null,
    ]);

    $this->user3 = User::forceCreate([
        'name' => 'Charlie Brown',
        'email' => 'charlie@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
});

it('can search users by name', function () {
    $response = $this->getJson('/api/search/users?query=Alice');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Alice Johnson']);
});

it('can search users by email', function () {
    $response = $this->getJson('/api/search/users?query=bob@example');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Bob Wilson']);
});

it('can search users with partial match', function () {
    $response = $this->getJson('/api/search/users?query=son');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');

    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names)->toContain('Alice Johnson')
        ->toContain('Bob Wilson');
});

it('can filter users by verified status', function () {
    $response = $this->getJson('/api/search/users?verified=1');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

it('can filter users by unverified status', function () {
    $response = $this->getJson('/api/search/users?verified=0');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Bob Wilson']);
});

it('can filter users by creation date range', function () {
    // Update user creation dates for testing
    $this->user1->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();
    $this->user2->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();
    $this->user3->forceFill(['created_at' => now()->subDay()])->saveQuietly();

    $from = now()->subDays(6)->toDateString();
    $to = now()->toDateString();

    $response = $this->getJson("/api/search/users?created_from={$from}&created_to={$to}");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

it('can sort users by name', function () {
    $response = $this->getJson('/api/search/users?order_by=name&order_direction=asc');

    $response->assertStatus(200);

    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names[0])->toBe('Alice Johnson');
});

it('can paginate users', function () {
    // Create more users
    for ($i = 1; $i <= 20; $i++) {
        User::forceCreate([
            'name' => "User {$i}",
            'email' => "user{$i}@example.com",
            'password' => bcrypt('password'),
        ]);
    }

    $response = $this->getJson('/api/search/users?per_page=10');

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

it('validates user search order_by field', function () {
    $response = $this->getJson('/api/search/users?order_by=invalid_field');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['order_by']);
});

it('validates user search order_direction', function () {
    $response = $this->getJson('/api/search/users?order_direction=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['order_direction']);
});

it('can filter users by role', function () {
    // Spatie permissions are team-scoped (config/permission.php teams=true), so role
    // creation + assignment must happen inside a team context, otherwise the
    // model_has_roles.team_id NOT NULL constraint fails. The public search route runs
    // in-process, so the role scope resolves against this same team id.
    $team = Team::factory()->create(['user_id' => $this->user1->id]);
    setPermissionsTeamId($team->id);

    $editor = Role::create(['name' => 'editor']);
    $this->user1->assignRole($editor);

    $response = $this->getJson('/api/search/users?role=editor');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Alice Johnson']);
});

it('can combine multiple user filters', function () {
    $response = $this->getJson('/api/search/users?query=Alice&verified=1&order_by=name');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Alice Johnson']);
});
