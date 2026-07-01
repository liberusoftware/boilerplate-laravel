<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // A team requires an owner (user_id). Create a placeholder owner if the
        // users table is empty so the seeder is order-independent.
        $ownerId = User::query()->min('id')
            ?? User::factory()->create([
                'name' => 'Owner',
                'email' => 'owner@example.com',
            ])->id;

        Team::firstOrCreate(
            ['name' => 'Default'],
            ['personal_team' => false, 'user_id' => $ownerId],
        );
    }
}
