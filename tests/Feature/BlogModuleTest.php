<?php

namespace Tests\Feature;

use App\Modules\BlogModule\Services\BlogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogModuleTest extends TestCase
{
    use RefreshDatabase;

    protected BlogService $blogService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->blogService = app(BlogService::class);
    }

    /** @test */
    public function it_can_get_all_blog_posts()
    {
        $posts = $this->blogService->getAllPosts();
        
        $this->assertIsArray($posts);
        $this->assertNotEmpty($posts);
        $this->assertArrayHasKey('title', $posts[0]);
        $this->assertArrayHasKey('content', $posts[0]);
    }

    /** @test */
    public function it_can_get_specific_blog_post()
    {
        $post = $this->blogService->getPost(1);
        
        $this->assertNotNull($post);
        $this->assertEquals(1, $post['id']);
        $this->assertArrayHasKey('title', $post);
        $this->assertArrayHasKey('content', $post);
    }

    /** @test */
    public function it_returns_null_for_non_existent_post()
    {
        $post = $this->blogService->getPost(999);
        $this->assertNull($post);
    }

    /** @test */
    public function it_can_create_new_blog_post()
    {
        $data = [
            'title' => 'Test Post',
            'content' => 'This is a test post content.',
        ];

        $post = $this->blogService->createPost($data);
        
        $this->assertIsArray($post);
        $this->assertEquals($data['title'], $post['title']);
        $this->assertEquals($data['content'], $post['content']);
        $this->assertArrayHasKey('id', $post);
        $this->assertArrayHasKey('created_at', $post);
    }

    /** @test */
    public function it_can_access_blog_routes()
    {
        $response = $this->get('/blog');
        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_access_specific_blog_post_route()
    {
        $response = $this->get('/blog/1');
        $response->assertStatus(200);
    }

    /** @test */
    public function it_returns_404_for_non_existent_blog_post()
    {
        $response = $this->get('/blog/999');
        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_create_blog_post_via_api()
    {
        $data = [
            'title' => 'API Test Post',
            'content' => 'This post was created via API.',
        ];

        $response = $this->postJson('/api/blog', $data);
        
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'title',
            'content',
            'created_at',
        ]);
    }

    /** @test */
    public function it_validates_blog_post_creation()
    {
        $response = $this->postJson('/api/blog', []);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'content']);
    }
}