<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($this->user, 'sanctum');
});

it('can search all registered entity types with a query', function () {
    $response = $this->getJson('/api/search/all?query=Test');

    $response->assertStatus(200)
        ->assertJsonStructure(['users']);

    expect($response->json('users.total'))->toBe(1);
});

it('can search specific entity types', function () {
    $response = $this->getJson('/api/search/all?query=Test&types[]=users');

    $response->assertStatus(200)
        ->assertJsonStructure(['users']);
});

it('rejects a type no searcher is registered for', function () {
    $response = $this->getJson('/api/search/all?types[]=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['types.0']);
});

it('respects per_page limit for all searches', function () {
    User::factory()->count(10)->create();

    $response = $this->getJson('/api/search/all?per_page=3');

    $response->assertStatus(200);

    expect(count($response->json('users.data')))->toBeLessThanOrEqual(3);
});

it('returns empty results when no matches found', function () {
    $response = $this->getJson('/api/search/all?query=NonExistent');

    $response->assertStatus(200);

    expect($response->json('users.total'))->toBe(0);
});
