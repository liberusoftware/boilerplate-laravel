<?php

use App\Models\SiteSettings;

it('can create and read site settings model', function () {
    $settings = SiteSettings::create([
        'name' => 'Acme',
        'email' => 'info@acme.test'
    ]);

    $found = SiteSettings::find($settings->id);
    expect($found)->not->toBeNull();
    expect($found->name)->toBe('Acme');
    expect($found->email)->toBe('info@acme.test');
});
