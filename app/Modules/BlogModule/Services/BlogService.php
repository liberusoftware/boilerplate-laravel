<?php

namespace App\Modules\BlogModule\Services;

class BlogService
{
    /**
     * Get all blog posts.
     */
    public function getAllPosts(): array
    {
        // This would typically interact with a model/database
        return [
            [
                'id' => 1,
                'title' => 'Welcome to the Blog Module',
                'content' => 'This is a demonstration of the modular architecture.',
                'created_at' => now(),
            ],
            [
                'id' => 2,
                'title' => 'Custom Modules Made Easy',
                'content' => 'Learn how to create and integrate custom modules.',
                'created_at' => now()->subDay(),
            ],
        ];
    }

    /**
     * Get a specific blog post.
     */
    public function getPost(int $id): ?array
    {
        $posts = $this->getAllPosts();
        return collect($posts)->firstWhere('id', $id);
    }

    /**
     * Create a new blog post.
     */
    public function createPost(array $data): array
    {
        // This would typically save to database
        return [
            'id' => rand(1000, 9999),
            'title' => $data['title'],
            'content' => $data['content'],
            'created_at' => now(),
        ];
    }
}