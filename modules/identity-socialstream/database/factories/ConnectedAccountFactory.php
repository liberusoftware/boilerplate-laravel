<?php

namespace Liberu\Foundation\Identity\Socialstream\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JoelButcher\Socialstream\Providers;
use Liberu\Foundation\Identity\Socialstream\Models\ConnectedAccount;

/**
 * @extends Factory<ConnectedAccount>
 */
class ConnectedAccountFactory extends Factory
{
    protected $model = ConnectedAccount::class;

    public function definition(): array
    {
        return [
            'provider' => $this->faker->randomElement(Providers::all()),
            'provider_id' => $this->faker->numerify('########'),
            'token' => Str::random(432),
            'refresh_token' => Str::random(432),
        ];
    }
}
