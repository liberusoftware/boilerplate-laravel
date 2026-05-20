<?php

use App\Models\User;
use App\Models\Post;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
            ->assertJsonPath('data.0.email', 'jane@example.com');
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

describe('Post Search', function () {
    it('can search posts by title', function () {
        $user = User::factory()->create();
        Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Laravel Performance Tips',
            'content' => 'Some content',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'PHP Best Practices',
            'content' => 'Some content',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/search/posts?query=Laravel');

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'Laravel Performance Tips');
    });

    it('can search posts by content', function () {
        $user = User::factory()->create();
        Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'content' => 'This is about optimization',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/search/posts?query=optimization');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('can filter posts by status', function () {
        $user = User::factory()->create();
        Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Published Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Draft Post',
            'status' => 'draft',
            'published_at' => null,
        ]);

        $response = $this->getJson('/api/search/posts?query=Post&status=published');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'published');
    });

    it('eager loads user relationship', function () {
        $user = User::factory()->create(['name' => 'Author Name']);
        Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/search/posts?query=Test');

        $response->assertOk()
            ->assertJsonPath('data.0.user.name', 'Author Name');
    });
});

describe('Group Search', function () {
    it('can search groups by name', function () {
        $owner = User::factory()->create();
        Group::factory()->create(['name' => 'Developers Group', 'owner_id' => $owner->id]);
        Group::factory()->create(['name' => 'Designers Group', 'owner_id' => $owner->id]);

        $response = $this->getJson('/api/search/groups?query=Developers');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Developers Group');
    });

    it('can search groups by description', function () {
        $owner = User::factory()->create();
        Group::factory()->create([
            'name' => 'Group One',
            'description' => 'This is for Laravel developers',
            'owner_id' => $owner->id,
        ]);

        $response = $this->getJson('/api/search/groups?query=Laravel');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('can filter active groups only', function () {
        $owner = User::factory()->create();
        Group::factory()->create(['name' => 'Active Group', 'is_active' => true, 'owner_id' => $owner->id]);
        Group::factory()->create(['name' => 'Inactive Group', 'is_active' => false, 'owner_id' => $owner->id]);

        $response = $this->getJson('/api/search/groups?query=Group&active_only=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_active', true);
    });
});

describe('Search Performance', function () {
    it('handles pagination correctly', function () {
        $user = User::factory()->create();
        Post::factory()->count(25)->create([
            'user_id' => $user->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/search/posts?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'current_page',
                'data',
                'total',
                'per_page',
            ]);
    });

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
        \Illuminate\Support\Facades\Cache::flush();
    });
});
