<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\Group;
use Illuminate\Database\Seeder;

class SearchDataSeeder extends Seeder
{
    /**
     * Seed the database with sample data for search functionality testing.
     */
    public function run(): void
    {
        // Create users if they don't exist
        $users = User::all();
        if ($users->count() < 10) {
            $users = User::factory()->count(10)->create();
        }

        // Create groups
        Group::factory()->count(20)->create();

        // Create posts with various statuses
        foreach ($users as $user) {
            Post::factory()->count(5)->create([
                'user_id' => $user->id,
                'status' => 'published',
            ]);

            Post::factory()->count(2)->create([
                'user_id' => $user->id,
                'status' => 'draft',
            ]);

            Post::factory()->count(1)->create([
                'user_id' => $user->id,
                'status' => 'archived',
            ]);
        }

        $this->command->info('Search test data seeded successfully!');
    }
}
