<?php

use App\Models\User;
use App\Models\Post;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test user
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    // Create test posts
    Post::create([
        'title' => 'Laravel Guide',
        'content' => 'Complete Laravel tutorial',
        'author_id' => $this->user->id,
        'status' => 'published',
        'published_at' => now(),
    ]);

    // Create test groups
    Group::create([
        'name' => 'Laravel Developers',
        'description' => 'Laravel community group',
        'owner_id' => $this->user->id,
        'type' => 'public',
    ]);
});

it('can search all entities with a query', function () {
    $response = $this->getJson('/api/search/all?query=Laravel');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'users',
            'posts',
            'groups',
        ]);
    
    $data = $response->json();
    expect($data['posts']['total'])->toBe(1);
    expect($data['groups']['total'])->toBe(1);
});

it('can search specific entity types', function () {
    $response = $this->getJson('/api/search/all?query=Laravel&types[]=posts');

    $response->assertStatus(200)
        ->assertJsonStructure(['posts'])
        ->assertJsonMissing(['users', 'groups']);
});

it('can search multiple specific entity types', function () {
    $response = $this->getJson('/api/search/all?query=Laravel&types[]=posts&types[]=groups');

    $response->assertStatus(200)
        ->assertJsonStructure(['posts', 'groups'])
        ->assertJsonMissing(['users']);
});

it('validates search types', function () {
    $response = $this->getJson('/api/search/all?types[]=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['types.0']);
});

it('respects per_page limit for all searches', function () {
    // Create multiple entities
    for ($i = 1; $i <= 10; $i++) {
        Post::create([
            'title' => "Post {$i}",
            'content' => "Content {$i}",
            'author_id' => $this->user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    $response = $this->getJson('/api/search/all?per_page=3');

    $response->assertStatus(200);
    
    $data = $response->json();
    expect(count($data['posts']['data']))->toBeLessThanOrEqual(3);
});

it('returns empty results when no matches found', function () {
    $response = $this->getJson('/api/search/all?query=NonExistent');

    $response->assertStatus(200);
    
    $data = $response->json();
    expect($data['users']['total'])->toBe(0);
    expect($data['posts']['total'])->toBe(0);
    expect($data['groups']['total'])->toBe(0);
});
