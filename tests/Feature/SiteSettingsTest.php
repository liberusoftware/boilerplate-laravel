<?php

use App\Settings\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed the required settings rows so SiteSettings can be resolved
    DB::table('settings')->upsert([
        ['group' => 'site', 'name' => 'site_name', 'locked' => false, 'payload' => json_encode('Initial'), 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'site', 'name' => 'site_email', 'locked' => false, 'payload' => json_encode('init@example.com'), 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'site', 'name' => 'site_phone', 'locked' => false, 'payload' => json_encode(null), 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'site', 'name' => 'site_address', 'locked' => false, 'payload' => json_encode(null), 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'site', 'name' => 'site_country', 'locked' => false, 'payload' => json_encode(null), 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'site', 'name' => 'site_currency', 'locked' => false, 'payload' => json_encode('$'), 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'site', 'name' => 'site_default_language', 'locked' => false, 'payload' => json_encode('en'), 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'site', 'name' => 'facebook_url', 'locked' => false, 'payload' => json_encode(null), 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'site', 'name' => 'twitter_url', 'locked' => false, 'payload' => json_encode(null), 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'site', 'name' => 'github_url', 'locked' => false, 'payload' => json_encode(null), 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'site', 'name' => 'youtube_url', 'locked' => false, 'payload' => json_encode(null), 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'site', 'name' => 'footer_copyright', 'locked' => false, 'payload' => json_encode('© 2025'), 'created_at' => now(), 'updated_at' => now()],
    ], ['group', 'name'], ['payload', 'updated_at']);
});

it('can create and read site settings via Spatie typed settings', function () {
    /** @var SiteSettings $settings */
    $settings = app(SiteSettings::class);
    $settings->site_name = 'Acme';
    $settings->site_email = 'info@acme.test';

    $settings->save();

    // Fresh instance to confirm persistence
    app()->forgetInstance(SiteSettings::class);
    $loaded = app(SiteSettings::class);
    expect($loaded->site_name)->toBe('Acme');
    expect($loaded->site_email)->toBe('info@acme.test');
});
