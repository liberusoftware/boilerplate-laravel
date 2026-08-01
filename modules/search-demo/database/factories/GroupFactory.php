<?php

namespace Liberu\Search\Demo\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Liberu\Search\Demo\Models\Group;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actorModel = config('auth.providers.users.model');

        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'owner_id' => $actorModel::factory(),
            'type' => fake()->randomElement(['public', 'private', 'restricted']),
            'is_active' => fake()->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the group is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the group is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
