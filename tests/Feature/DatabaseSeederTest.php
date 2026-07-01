<?php

use App\Models\Group;
use App\Models\Post;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

use function Pest\Laravel\seed;

it('seeds a default team, admin user with super_admin role, and sample content', function () {
    seed(DatabaseSeeder::class);

    // Default team
    $team = Team::where('name', 'Default')->first();
    expect($team)->not->toBeNull();

    // Admin user on the team
    $admin = User::where('email', 'admin@example.com')->first();
    expect($admin)->not->toBeNull();
    expect($admin->teams()->where('teams.id', $team->id)->exists())->toBeTrue();

    // super_admin role assigned in the team's permission context
    setPermissionsTeamId($team->id);
    expect(Role::where('name', 'super_admin')->exists())->toBeTrue();
    expect($admin->fresh()->hasRole('super_admin'))->toBeTrue();

    // Sample content for search/demo
    expect(Post::count())->toBeGreaterThan(0);
    expect(Group::count())->toBeGreaterThan(0);
});
