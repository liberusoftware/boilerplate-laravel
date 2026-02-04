<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modules Path
    |--------------------------------------------------------------------------
    |
    | This value determines the path where modules are stored. By default,
    | modules are stored in the app/Modules directory.
    |
    */

    'path' => app_path('Modules'),

    /*
    |--------------------------------------------------------------------------
    | Auto Discovery
    |--------------------------------------------------------------------------
    |
    | When enabled, the module system will automatically discover and register
    | modules found in the modules directory.
    |
    */

    'auto_discovery' => true,

    /*
    |--------------------------------------------------------------------------
    | Development Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, additional debugging information will be available
    | and modules will be reloaded on each request.
    |
    */

    'development' => env('MODULES_DEVELOPMENT', env('APP_DEBUG', false)),

    /*
    |--------------------------------------------------------------------------
    | Cache Modules
    |--------------------------------------------------------------------------
    |
    | When enabled, module information will be cached to improve performance.
    | This is recommended for production environments.
    |
    */

    'cache' => env('MODULES_CACHE', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Key
    |--------------------------------------------------------------------------
    |
    | The cache key used to store module information.
    |
    */

    'cache_key' => 'app.modules',

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | The time-to-live for cached module information in seconds.
    |
    */

    'cache_ttl' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Module Requirements
    |--------------------------------------------------------------------------
    |
    | Global requirements that modules must meet.
    |
    */

    'requirements' => [
        'php' => '8.4',
        'laravel' => '12.0',
    ],

];