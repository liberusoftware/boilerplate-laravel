<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users or create one
        $users = User::all();
        if ($users->isEmpty()) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
            $users = collect([$user]);
        }

        // Create sample posts
        $posts = [
            [
                'title' => 'Getting Started with Laravel',
                'content' => 'Laravel is a web application framework with expressive, elegant syntax. This comprehensive guide will walk you through the basics of getting started with Laravel development.',
                'status' => 'published',
                'published_at' => now()->subDays(10),
            ],
            [
                'title' => 'Advanced Search Techniques',
                'content' => 'Learn how to implement advanced search functionality in your Laravel applications with filters, sorting, and pagination.',
                'status' => 'published',
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'Building RESTful APIs',
                'content' => 'A comprehensive tutorial on building RESTful APIs with Laravel, including authentication, validation, and best practices.',
                'status' => 'published',
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Understanding Eloquent ORM',
                'content' => 'Eloquent is Laravel\'s powerful ORM. This article covers relationships, scopes, and advanced query techniques.',
                'status' => 'published',
                'published_at' => now()->subDays(1),
            ],
            [
                'title' => 'Draft Post - Work in Progress',
                'content' => 'This is a draft post that is still being worked on.',
                'status' => 'draft',
                'published_at' => null,
            ],
            [
                'title' => 'Archived Legacy Article',
                'content' => 'This is an archived article from our old blog.',
                'status' => 'archived',
                'published_at' => now()->subMonths(6),
            ],
        ];

        foreach ($posts as $postData) {
            Post::create(array_merge($postData, [
                'author_id' => $users->random()->id,
            ]));
        }
    }
}
