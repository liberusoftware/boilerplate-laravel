<?php

namespace Database\Seeders;

use App\Models\Team;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

        // Roles are team-scoped (permission.teams=true). Create + query them
        // inside the default team's context. See CLAUDE.md tenancy rules.
        if (Utils::isTenancyEnabled()) {
            $team = Team::firstOrFail();
            $roleData['team_id'] = $team->id;
            setPermissionsTeamId($team->id);
        }

        $adminRole = Role::firstOrCreate($roleData);

        // Grant every generated web permission (none until shield:generate runs — harmless).
        $permissions = Permission::where('guard_name', 'web')->pluck('id')->toArray();
        $adminRole->syncPermissions($permissions);
    }
}
