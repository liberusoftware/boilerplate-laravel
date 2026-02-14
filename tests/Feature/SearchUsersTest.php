<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test users
    $this->user1 = User::create([
        'name' => 'Alice Johnson',
        'email' => 'alice@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->user2 = User::create([
        'name' => 'Bob Wilson',
        'email' => 'bob@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => null,
    ]);

    $this->user3 = User::create([
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
        ->assertJsonFragment(['email' => 'bob@example.com']);
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
    
    $data = $response->json('data');
    expect($data)->each(fn ($user) => expect($user['email_verified_at'])->not->toBeNull());
});

it('can filter users by unverified status', function () {
    $response = $this->getJson('/api/search/users?verified=0');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['email' => 'bob@example.com']);
});

it('can filter users by creation date range', function () {
    // Update user creation dates for testing
    $this->user1->update(['created_at' => now()->subDays(10)]);
    $this->user2->update(['created_at' => now()->subDays(5)]);
    $this->user3->update(['created_at' => now()->subDay()]);

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
        User::create([
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

it('can combine multiple user filters', function () {
    $response = $this->getJson('/api/search/users?query=Alice&verified=1&order_by=name');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'Alice Johnson']);
});
