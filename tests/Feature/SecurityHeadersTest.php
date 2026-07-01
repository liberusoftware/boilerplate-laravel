<?php

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('adds baseline security headers to web responses', function () {
    $response = $this->get('/');

    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
    $response->assertHeader('Permissions-Policy');
});

it('does not send HSTS over plain HTTP', function () {
    // Local/dev http requests must not be pinned to HTTPS.
    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
});

// The Filament panels use their own middleware stack (not the web group), so the
// headers must be registered there too. Use an authenticated request so the response
// unwinds back through the panel middleware (a guest redirect comes from an auth
// exception, which bypasses post-response middleware).
it('adds security headers to the app panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/app')->assertHeader('X-Frame-Options', 'DENY');
});

it('adds security headers to the admin panel', function () {
    $admin = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $admin->id]);
    $admin->forceFill(['current_team_id' => $team->id])->save();
    setPermissionsTeamId($team->id);
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'team_id' => $team->id]);
    $admin->assignRole('super_admin');

    $this->actingAs($admin)->get('/admin/'.$team->id)->assertHeader('X-Frame-Options', 'DENY');
});
