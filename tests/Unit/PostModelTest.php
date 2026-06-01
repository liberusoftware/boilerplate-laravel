<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has fillable attributes', function () {
    $post = new Post;
    expect($post->getFillable())->toContain('title', 'content', 'user_id', 'status', 'published_at');
});

it('casts published_at as datetime', function () {
    $casts = (new Post)->getCasts();
    expect($casts)->toHaveKey('published_at');
});

it('user relationship resolves correctly', function () {
    $user = User::factory()->create();
    $post = Post::create(['title' => 'Test', 'content' => 'Body', 'user_id' => $user->id, 'status' => 'draft']);

    expect($post->user)->toBeInstanceOf(User::class);
    expect($post->user->id)->toBe($user->id);
});

it('author is alias for user relationship', function () {
    $user = User::factory()->create();
    $post = Post::create(['title' => 'Test', 'content' => 'Body', 'user_id' => $user->id, 'status' => 'draft']);

    expect($post->author->id)->toBe($user->id);
});

it('scopePublished only returns published posts with past date', function () {
    $user = User::factory()->create();
    Post::create(['title' => 'Published', 'content' => 'A', 'user_id' => $user->id, 'status' => 'published', 'published_at' => now()->subDay()]);
    Post::create(['title' => 'Draft', 'content' => 'B', 'user_id' => $user->id, 'status' => 'draft']);
    Post::create(['title' => 'Future', 'content' => 'C', 'user_id' => $user->id, 'status' => 'published', 'published_at' => now()->addDay()]);

    $published = Post::published()->get();
    expect($published)->toHaveCount(1);
    expect($published->first()->title)->toBe('Published');
});

it('scopeStatus filters by status', function () {
    $user = User::factory()->create();
    Post::create(['title' => 'A', 'content' => 'A', 'user_id' => $user->id, 'status' => 'published', 'published_at' => now()->subDay()]);
    Post::create(['title' => 'B', 'content' => 'B', 'user_id' => $user->id, 'status' => 'draft']);

    expect(Post::status('draft')->get())->toHaveCount(1);
    expect(Post::status('published')->get())->toHaveCount(1);
});

it('scopeByAuthor filters by user_id', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    Post::create(['title' => 'A', 'content' => 'A', 'user_id' => $user1->id, 'status' => 'draft']);
    Post::create(['title' => 'B', 'content' => 'B', 'user_id' => $user2->id, 'status' => 'draft']);

    expect(Post::byAuthor($user1->id)->get())->toHaveCount(1);
});

it('scopeDateRange filters by published_at range', function () {
    $user = User::factory()->create();
    Post::create(['title' => 'Old', 'content' => 'A', 'user_id' => $user->id, 'status' => 'published', 'published_at' => now()->subDays(10)]);
    Post::create(['title' => 'Recent', 'content' => 'B', 'user_id' => $user->id, 'status' => 'published', 'published_at' => now()->subDays(2)]);

    $filtered = Post::dateRange(now()->subDays(5)->toDateString(), now()->toDateString())->get();
    expect($filtered)->toHaveCount(1);
    expect($filtered->first()->title)->toBe('Recent');
});

it('scopeSearch filters by title and content', function () {
    $user = User::factory()->create();
    Post::create(['title' => 'Laravel Guide', 'content' => 'About Laravel', 'user_id' => $user->id, 'status' => 'draft']);
    Post::create(['title' => 'PHP Tips', 'content' => 'About PHP best practices', 'user_id' => $user->id, 'status' => 'draft']);

    expect(Post::search('Laravel')->get())->toHaveCount(1);
    expect(Post::search('best practices')->get())->toHaveCount(1);
});
