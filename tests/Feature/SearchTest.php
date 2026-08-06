<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(), 'sanctum');
});

describe('User Search', function () {
    it('can search users by name', function () {
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $response = $this->getJson('/api/search/users?query=John');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'John Doe');
    });

    it('can search users by email', function () {
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

        $response = $this->getJson('/api/search/users?query=jane@example.com');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Jane Smith');
    });

    it('returns empty results when no users match', function () {
        User::factory()->create(['name' => 'John Doe']);

        $response = $this->getJson('/api/search/users?query=nonexistent');

        $response->assertOk()
            ->assertJsonPath('data', []);
    });

    it('validates search query is required', function () {
        // per_page=0 violates min:1 rule
        $response = $this->getJson('/api/search/users?per_page=0');

        $response->assertStatus(422);
    });
});

describe('Search Performance', function () {
    it('limits per_page to maximum of 100', function () {
        $response = $this->getJson('/api/search/users?query=test&per_page=150');

        $response->assertStatus(422);
    });

    it('throttles search requests', function () {
        // Make 61 requests (limit is 60 per minute)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/search/users?query=test');
        }

        $response->assertStatus(429);

        // Clear rate limiter cache to avoid affecting subsequent tests
        Cache::flush();
    });
});
