<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modules Path
    |--------------------------------------------------------------------------
    |
    | The path where modules are stored. By default, modules live in the
    | app/Modules directory.
    |
    */

    'path' => app_path('Modules'),

    /*
    |--------------------------------------------------------------------------
    | Development Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, modules are reloaded on each request (caching is skipped).
    |
    */

    'development' => env('MODULES_DEVELOPMENT', env('APP_DEBUG', false)),

    /*
    |--------------------------------------------------------------------------
    | Cache Modules
    |--------------------------------------------------------------------------
    |
    | When enabled, the loaded module collection is cached for performance.
    | Disabled by default to keep discovery deterministic.
    |
    */

    'cache' => env('MODULES_CACHE', false),

    /*
    |--------------------------------------------------------------------------
    | Cache Key / TTL
    |--------------------------------------------------------------------------
    */

    'cache_key' => 'app.modules',

    'cache_ttl' => 3600,

];
