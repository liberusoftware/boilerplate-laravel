<?php

namespace Liberu\Search\Demo\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Liberu\Search\Demo\Models\Post;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actorModel = config('auth.providers.users.model');

        return [
            'user_id' => $actorModel::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
        ];
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            // scopePublished() also requires a non-null, past published_at.
            'published_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the post is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }
}
