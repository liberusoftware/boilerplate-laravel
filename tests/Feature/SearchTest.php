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
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.data.0.name', 'John Doe');
    });

    it('can search users by email', function () {
        User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        
        $response = $this->getJson('/api/search/users?query=jane@example.com');
        
        $response->assertOk()
            ->assertJsonPath('data.data.0.email', 'jane@example.com');
    });

    it('returns empty results when no users match', function () {
        User::factory()->create(['name' => 'John Doe']);
        
        $response = $this->getJson('/api/search/users?query=nonexistent');
        
        $response->assertOk()
            ->assertJsonPath('data.data', []);
    });

    it('validates search query is required', function () {
        $response = $this->getJson('/api/search/users');
        
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
        ]);
        Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'PHP Best Practices',
            'content' => 'Some content',
        ]);
        
        $response = $this->getJson('/api/search/posts?query=Laravel');
        
        $response->assertOk()
            ->assertJsonPath('data.data.0.title', 'Laravel Performance Tips');
    });

    it('can search posts by content', function () {
        $user = User::factory()->create();
        Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Post',
            'content' => 'This is about optimization',
        ]);
        
        $response = $this->getJson('/api/search/posts?query=optimization');
        
        $response->assertOk()
            ->assertJsonCount(1, 'data.data');
    });

    it('can filter posts by status', function () {
        $user = User::factory()->create();
        Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Published Post',
            'status' => 'published',
        ]);
        Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Draft Post',
            'status' => 'draft',
        ]);
        
        $response = $this->getJson('/api/search/posts?query=Post&status=published');
        
        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.status', 'published');
    });

    it('eager loads user relationship', function () {
        $user = User::factory()->create(['name' => 'Author Name']);
        Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Post',
        ]);
        
        $response = $this->getJson('/api/search/posts?query=Test');
        
        $response->assertOk()
            ->assertJsonPath('data.data.0.user.name', 'Author Name');
    });
});

describe('Group Search', function () {
    it('can search groups by name', function () {
        Group::factory()->create(['name' => 'Developers Group']);
        Group::factory()->create(['name' => 'Designers Group']);
        
        $response = $this->getJson('/api/search/groups?query=Developers');
        
        $response->assertOk()
            ->assertJsonPath('data.data.0.name', 'Developers Group');
    });

    it('can search groups by description', function () {
        Group::factory()->create([
            'name' => 'Group One',
            'description' => 'This is for Laravel developers',
        ]);
        
        $response = $this->getJson('/api/search/groups?query=Laravel');
        
        $response->assertOk()
            ->assertJsonCount(1, 'data.data');
    });

    it('can filter active groups only', function () {
        Group::factory()->create([
            'name' => 'Active Group',
            'is_active' => true,
        ]);
        Group::factory()->create([
            'name' => 'Inactive Group',
            'is_active' => false,
        ]);
        
        $response = $this->getJson('/api/search/groups?query=Group&active_only=true');
        
        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.is_active', true);
    });
});

describe('Search Performance', function () {
    it('handles pagination correctly', function () {
        $user = User::factory()->create();
        Post::factory()->count(25)->create(['user_id' => $user->id]);
        
        $response = $this->getJson('/api/search/posts?query=&per_page=10');
        
        $response->assertOk()
            ->assertJsonCount(10, 'data.data')
            ->assertJsonStructure([
                'data' => [
                    'current_page',
                    'data',
                    'total',
                    'per_page',
                ],
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
    });
});
