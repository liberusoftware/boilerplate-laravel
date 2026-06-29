<?php

use App\Settings\SiteSettings;

it('seeds default site settings via the settings migration', function () {
    $settings = app(SiteSettings::class);

    expect($settings->site_currency)->toBe('$')
        ->and($settings->site_default_language)->toBe('en')
        ->and($settings->github_url)->toContain('github.com/liberusoftware');
});
