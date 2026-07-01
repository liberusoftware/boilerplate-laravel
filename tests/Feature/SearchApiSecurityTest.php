<?php

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('unauthenticated access', function () {
    it('rejects unauthenticated users search', function () {
        $this->getJson('/api/search/users')->assertStatus(401);
    });

    it('rejects unauthenticated posts search', function () {
        $this->getJson('/api/search/posts')->assertStatus(401);
    });

    it('rejects unauthenticated groups search', function () {
        $this->getJson('/api/search/groups')->assertStatus(401);
    });

    it('rejects unauthenticated all search', function () {
        $this->getJson('/api/search/all')->assertStatus(401);
    });
});

describe('draft posts stay hidden', function () {
    it('never returns a draft even with include_drafts=1', function () {
        Post::create([
            'title' => 'Secret Draft',
            'content' => 'Not for public eyes',
            'user_id' => $this->user->id,
            'status' => 'draft',
            'published_at' => null,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/search/posts?include_drafts=1&status=draft');

        $response->assertStatus(200)
            ->assertJsonMissing(['title' => 'Secret Draft'])
            ->assertJsonCount(0, 'data');
    });
});

describe('non-public groups stay hidden', function () {
    it('excludes private and restricted groups but returns public ones', function () {
        $public = Group::create([
            'name' => 'Public Group',
            'description' => 'Anyone can see this',
            'owner_id' => $this->user->id,
            'type' => 'public',
        ]);

        Group::create([
            'name' => 'Private Group',
            'description' => 'Members only',
            'owner_id' => $this->user->id,
            'type' => 'private',
        ]);

        Group::create([
            'name' => 'Restricted Group',
            'description' => 'Invite only',
            'owner_id' => $this->user->id,
            'type' => 'restricted',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/search/groups');

        $response->assertStatus(200)
            ->assertJsonMissing(['name' => 'Private Group'])
            ->assertJsonMissing(['name' => 'Restricted Group'])
            ->assertJsonFragment(['name' => 'Public Group'])
            ->assertJsonCount(1, 'data');

        expect($response->json('data.0.id'))->toBe($public->id);
    });
});
