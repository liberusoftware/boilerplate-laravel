<?php

use Illuminate\Support\Facades\Blade;

it('registers a compilable themeVite directive', function () {
    // Blade compiles @themeVite to a call into the theme asset loader.
    $compiled = Blade::compileString('@themeVite');

    expect($compiled)
        ->toContain('activeCssEntry')
        ->toContain('resources/js/app.js');
});

it('renders the welcome page without error', function () {
    $this->get('/')->assertOk();
});
