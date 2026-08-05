<?php

namespace Tests\Fixtures\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Tests\Fixtures\Models\Group;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    protected $model = Group::class;

    /**
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
            'is_active' => fake()->boolean(80),
        ];
    }
}
