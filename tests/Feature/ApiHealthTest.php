<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('GET /up returns 200', function () {
    $this->get('/up')->assertOk();
});

it('GET /api/user requires authentication', function () {
    $this->getJson('/api/user')->assertUnauthorized();
});

it('authenticated GET /api/user returns user data', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonFragment(['email' => $user->email]);
});

it('search api requires valid per_page', function () {
    $this->getJson('/api/search/users?per_page=0')
        ->assertStatus(422);
});

it('search api enforces max per_page of 100', function () {
    $this->getJson('/api/search/users?per_page=200')
        ->assertStatus(422);
});

it('search api returns structured response for users', function () {
    $this->getJson('/api/search/users')
        ->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
});

it('search api returns structured response for posts', function () {
    $this->getJson('/api/search/posts')
        ->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
});

it('search api returns structured response for groups', function () {
    $this->getJson('/api/search/groups')
        ->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
});

it('search all api returns users posts and groups keys', function () {
    $this->getJson('/api/search/all')
        ->assertOk()
        ->assertJsonStructure(['users', 'posts', 'groups']);
});
