<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    |
    | The theme used when a user has no stored preference. Themes themselves are
    | discovered from the `themes/` directory (each with a theme.json), so this
    | is the only value the application reads.
    |
    */

    'default' => env('THEME_DEFAULT', 'default'),

    'fallback' => env('THEME_FALLBACK', 'default'),

    'surfaces' => [
        'public' => env('THEME_PUBLIC', 'clear-signal'),
        'portal' => env('THEME_PORTAL', 'default'),
        'admin' => env('THEME_ADMIN', 'default'),
    ],

    'sites' => [],
    'tenants' => [],
    'cache' => (bool) env('THEMES_CACHE', false),
    'cache_path' => base_path('bootstrap/cache/liberu-themes.php.cache'),
    'budgets' => ['css_kib' => 80, 'js_kib' => 40],

];
