<?php

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new SearchService;
});

describe('searchUsers', function () {
    it('returns paginated users', function () {
        User::factory()->count(5)->create();
        $result = $this->service->searchUsers([]);
        expect($result->total())->toBeGreaterThanOrEqual(5);
    });

    it('filters by search query', function () {
        User::factory()->create(['name' => 'UniqueSearchName']);
        User::factory()->create(['name' => 'SomethingElse']);

        $result = $this->service->searchUsers(['query' => 'UniqueSearch']);
        expect($result->total())->toBe(1);
        expect($result->items()[0]->name)->toBe('UniqueSearchName');
    });

    it('filters by verified status', function () {
        User::factory()->create(['email_verified_at' => now()]);
        User::factory()->create(['email_verified_at' => null]);

        $verified = $this->service->searchUsers(['verified' => true]);
        expect($verified->items())->each(fn ($user) => $user->email_verified_at->not->toBeNull());

        $unverified = $this->service->searchUsers(['verified' => false]);
        expect($unverified->items())->each(fn ($user) => $user->email_verified_at->toBeNull());
    });

    it('respects per_page setting', function () {
        User::factory()->count(20)->create();
        $result = $this->service->searchUsers(['per_page' => 5]);
        expect(count($result->items()))->toBeLessThanOrEqual(5);
    });

    it('orders by specified field', function () {
        User::factory()->create(['name' => 'Zara']);
        User::factory()->create(['name' => 'Adam']);

        $result = $this->service->searchUsers(['order_by' => 'name', 'order_direction' => 'asc']);
        $names = array_column($result->items(), 'name');
        expect($names)->toContain('Adam');
        expect($names[0])->toBe('Adam');
    });
});

describe('searchPosts', function () {
    it('excludes drafts by default', function () {
        $user = User::factory()->create();
        Post::create(['title' => 'Published', 'content' => 'A', 'user_id' => $user->id, 'status' => 'published', 'published_at' => now()->subDay()]);
        Post::create(['title' => 'Draft', 'content' => 'B', 'user_id' => $user->id, 'status' => 'draft']);

        $result = $this->service->searchPosts([]);
        $statuses = array_column($result->items(), 'status');
        expect($statuses)->not->toContain('draft');
    });

    it('includes drafts when include_drafts is set', function () {
        $user = User::factory()->create();
        Post::create(['title' => 'Draft', 'content' => 'B', 'user_id' => $user->id, 'status' => 'draft']);

        $result = $this->service->searchPosts(['include_drafts' => true, 'status' => 'draft']);
        expect($result->total())->toBeGreaterThanOrEqual(1);
    });

    it('filters by author id', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Post::create(['title' => 'A', 'content' => 'A', 'user_id' => $user1->id, 'status' => 'published', 'published_at' => now()->subDay()]);
        Post::create(['title' => 'B', 'content' => 'B', 'user_id' => $user2->id, 'status' => 'published', 'published_at' => now()->subDay()]);

        $result = $this->service->searchPosts(['author_id' => $user1->id]);
        expect($result->total())->toBe(1);
    });
});

describe('searchGroups', function () {
    it('filters by active status', function () {
        $owner = User::factory()->create();
        Group::create(['name' => 'Active Group', 'owner_id' => $owner->id, 'type' => 'public', 'is_active' => true]);
        Group::create(['name' => 'Inactive Group', 'owner_id' => $owner->id, 'type' => 'public', 'is_active' => false]);

        $result = $this->service->searchGroups(['active_only' => true]);
        expect($result->total())->toBe(1);
        expect($result->items()[0]->name)->toBe('Active Group');
    });

    it('filters by type', function () {
        $owner = User::factory()->create();
        Group::create(['name' => 'Public', 'owner_id' => $owner->id, 'type' => 'public']);
        Group::create(['name' => 'Private', 'owner_id' => $owner->id, 'type' => 'private']);

        $result = $this->service->searchGroups(['type' => 'public']);
        expect($result->total())->toBe(1);
    });
});

describe('searchAll', function () {
    it('returns users posts and groups keys', function () {
        $result = $this->service->searchAll([]);
        expect($result)->toHaveKey('users');
        expect($result)->toHaveKey('posts');
        expect($result)->toHaveKey('groups');
    });

    it('can limit to specific types', function () {
        $result = $this->service->searchAll(['types' => ['users']]);
        expect($result)->toHaveKey('users');
        expect($result)->not->toHaveKey('posts');
        expect($result)->not->toHaveKey('groups');
    });
});
