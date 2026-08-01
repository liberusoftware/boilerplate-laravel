<?php

namespace Liberu\Foundation\Organizations\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Liberu\Foundation\Organizations\Models\Team;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Team>
     */
    protected $model = Team::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actorModel = config('auth.providers.users.model');

        return [
            'name' => $this->faker->unique()->company(),
            'user_id' => $actorModel::factory(),
            'personal_team' => true,
        ];
    }
}
