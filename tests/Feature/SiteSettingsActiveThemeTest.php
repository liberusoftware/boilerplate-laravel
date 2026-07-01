<?php

use App\Settings\SiteSettings;

it('exposes active_theme defaulting to the default theme', function () {
    expect(app(SiteSettings::class)->active_theme)->toBe('default');
});
