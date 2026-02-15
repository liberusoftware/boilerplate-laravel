<?php

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test users
    $this->user1 = User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->user2 = User::create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => null,
    ]);

    // Create test posts
    $this->post1 = Post::create([
        'title' => 'Laravel Tutorial',
        'content' => 'Learn Laravel framework basics',
        'author_id' => $this->user1->id,
        'status' => 'published',
        'published_at' => now()->subDays(5),
    ]);

    $this->post2 = Post::create([
        'title' => 'Advanced PHP',
        'content' => 'Advanced PHP programming techniques',
        'author_id' => $this->user2->id,
        'status' => 'published',
        'published_at' => now()->subDays(2),
    ]);

    $this->post3 = Post::create([
        'title' => 'Draft Article',
        'content' => 'This is a draft',
        'author_id' => $this->user1->id,
        'status' => 'draft',
        'published_at' => null,
    ]);
});

it('can search posts by title', function () {
    $response = $this->getJson('/api/search/posts?query=Laravel');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['title' => 'Laravel Tutorial']);
});

it('can search posts by content', function () {
    $response = $this->getJson('/api/search/posts?query=programming');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['title' => 'Advanced PHP']);
});

it('can filter posts by status', function () {
    $response = $this->getJson('/api/search/posts?status=draft&include_drafts=1');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['status' => 'draft']);
});

it('excludes draft posts by default', function () {
    $response = $this->getJson('/api/search/posts');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
    
    $data = $response->json('data');
    expect($data)->each(fn ($post) => expect($post['status'])->toBe('published'));
});

it('can filter posts by author', function () {
    $response = $this->getJson('/api/search/posts?author_id=' . $this->user1->id);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['author_id' => $this->user1->id]);
});

it('can filter posts by date range', function () {
    $from = now()->subDays(3)->toDateString();
    $to = now()->toDateString();

    $response = $this->getJson("/api/search/posts?published_from={$from}&published_to={$to}");

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['title' => 'Advanced PHP']);
});

it('can sort posts by title ascending', function () {
    $response = $this->getJson('/api/search/posts?order_by=title&order_direction=asc');

    $response->assertStatus(200);
    
    $titles = collect($response->json('data'))->pluck('title')->all();
    expect($titles[0])->toBe('Advanced PHP');
});

it('can sort posts by date descending', function () {
    $response = $this->getJson('/api/search/posts?order_by=published_at&order_direction=desc');

    $response->assertStatus(200);
    
    $data = $response->json('data');
    expect($data[0]['title'])->toBe('Advanced PHP');
});

it('can paginate posts', function () {
    // Create more posts
    for ($i = 1; $i <= 20; $i++) {
        Post::create([
            'title' => "Post {$i}",
            'content' => "Content {$i}",
            'author_id' => $this->user1->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    $response = $this->getJson('/api/search/posts?per_page=5');

    $response->assertStatus(200)
        ->assertJsonCount(5, 'data')
        ->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'per_page',
            'total',
        ]);
});

it('validates post search filters', function () {
    $response = $this->getJson('/api/search/posts?status=invalid');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

it('validates author_id exists', function () {
    $response = $this->getJson('/api/search/posts?author_id=99999');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['author_id']);
});

it('can combine multiple post filters', function () {
    $response = $this->getJson('/api/search/posts?query=PHP&status=published&order_by=title');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['title' => 'Advanced PHP']);
});
