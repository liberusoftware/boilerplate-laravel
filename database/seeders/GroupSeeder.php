<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
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

        // Create sample groups
        $groups = [
            [
                'name' => 'Laravel Developers',
                'description' => 'A community for Laravel developers to share knowledge and collaborate on projects.',
                'type' => 'public',
            ],
            [
                'name' => 'Advanced PHP Techniques',
                'description' => 'Discussion group for advanced PHP programming techniques and best practices.',
                'type' => 'public',
            ],
            [
                'name' => 'Private Beta Testers',
                'description' => 'Exclusive group for beta testers of our new features.',
                'type' => 'private',
            ],
            [
                'name' => 'Admin Team',
                'description' => 'Restricted group for administrative team members only.',
                'type' => 'restricted',
            ],
            [
                'name' => 'Open Source Contributors',
                'description' => 'Community of open source contributors working on various Laravel packages.',
                'type' => 'public',
            ],
        ];

        foreach ($groups as $groupData) {
            Group::create(array_merge($groupData, [
                'owner_id' => $users->random()->id,
            ]));
        }
    }
}
