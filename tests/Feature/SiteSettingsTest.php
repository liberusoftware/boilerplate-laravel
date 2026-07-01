<?php

use App\Settings\SiteSettings;

it('round-trips typed site settings through the container', function () {
    $settings = app(SiteSettings::class);
    $settings->site_name = 'Acme';
    $settings->site_email = 'info@acme.test';
    $settings->save();

    app()->forgetInstance(SiteSettings::class);

    $reloaded = app(SiteSettings::class);

    expect($reloaded->site_name)->toBe('Acme')
        ->and($reloaded->site_email)->toBe('info@acme.test');
});
