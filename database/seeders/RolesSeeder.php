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
        // Define all roles that can be used in the application
        $roles = ['super_admin', 'tenant', 'buyer', 'seller', 'landlord', 'contractor'];

        $team = null;
        if (Utils::isTenancyEnabled()) {
            $team = Team::firstOrFail();
        }

        foreach ($roles as $roleName) {
            $roleData = [
                'name' => $roleName,
                'guard_name' => 'web',
            ];

            if ($team) {
                $roleData["team_id"] = $team->id;
            }

            $role = Role::firstOrCreate($roleData);

            // Only super_admin gets all permissions
            if ($roleName === 'super_admin') {
                $permissions = Permission::where('guard_name', 'web')->pluck('id')->toArray();
                $role->syncPermissions($permissions);
            }
        }
    }
}
