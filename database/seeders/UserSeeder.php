<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminPassword = Str::random(12);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
            ],
        );

        $team = Team::firstOrFail();

        // TeamSeeder runs first and may have created a throwaway owner to satisfy
        // the team's user_id. Hand ownership to the admin and drop that placeholder.
        $placeholderOwnerId = $team->user_id;
        $team->forceFill(['user_id' => $adminUser->id])->save();

        $adminUser->teams()->syncWithoutDetaching([$team->id]);
        $adminUser->forceFill(['current_team_id' => $team->id])->save();

        if ($placeholderOwnerId !== null && $placeholderOwnerId !== $adminUser->id) {
            User::where('id', $placeholderOwnerId)
                ->where('email', 'owner@example.com')
                ->whereDoesntHave('ownedTeams')
                ->delete();
        }

        // Assign the role in the team's permission context (team-scoped roles).
        if (Utils::isTenancyEnabled()) {
            setPermissionsTeamId($team->id);
        }
        $role = Role::where('name', 'super_admin')->firstOrFail();
        $adminUser->assignRole($role);

        echo "Admin password: {$adminPassword}\n";
    }
}
