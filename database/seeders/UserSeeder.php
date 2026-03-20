<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $adminPassword = Str::random(12);
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make($adminPassword),
            'email_verified_at' => now(),
        ]);

        $team = Team::firstOrFail();
        $adminUser->teams()->syncWithoutDetaching([$team->id]);

        $role = Role::where('name', 'super_admin')->firstOrFail();
        $adminUser->assignRole($role);

        // Print passwords to console
        echo "Admin password: {$adminPassword}\n";
    }
}
