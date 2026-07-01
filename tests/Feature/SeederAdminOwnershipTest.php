<?php

use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

it('makes admin@example.com the owner of the Default team', function () {
    seed(TeamSeeder::class);
    seed(RolesSeeder::class);
    seed(UserSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    $team = Team::where('name', 'Default')->firstOrFail();

    expect($team->user_id)->toBe($admin->id);
    expect($admin->current_team_id)->toBe($team->id);
});

it('leaves no throwaway owner@example.com placeholder user', function () {
    seed(TeamSeeder::class);
    seed(RolesSeeder::class);
    seed(UserSeeder::class);

    expect(User::where('email', 'owner@example.com')->exists())->toBeFalse();
});
