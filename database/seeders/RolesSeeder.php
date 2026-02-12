<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use BezhanSalleh\FilamentShield\Support\Utils;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleData = [
            'name' => 'super_admin',
            'guard_name' => 'web',
        ];

        if (Utils::isTenancyEnabled()) {
            $team = Team::firstOrFail();
            $roleData["team_id"] = $team->id;
        }

        $adminRole = Role::firstOrCreate($roleData);

        $permissions = Permission::where('guard_name', 'web')->pluck('id')->toArray();
        $adminRole->syncPermissions($permissions);
    }
}
