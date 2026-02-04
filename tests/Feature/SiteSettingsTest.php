<?php

use App\Settings\SiteSettings;

beforeEach(function () {
    config(['settings.driver' => 'array']);
});

it('can create and read site settings via Spatie typed settings', function () {
    /** @var SiteSettings $settings */
    $settings = app(SiteSettings::class);
    $settings->site_name = 'Acme';
    $settings->email = 'info@acme.test';

    if (method_exists($settings, 'save')) {
        $settings->save();
    }

    $loaded = app(SiteSettings::class);
    expect($loaded->site_name)->toBe('Acme');
    expect($loaded->email)->toBe('info@acme.test');
});
