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

    /*
    |--------------------------------------------------------------------------
    | External Module Paths
    |--------------------------------------------------------------------------
    |
    | Additional paths to scan for modules. Useful for loading modules from
    | composer packages or custom locations.
    |
    */

    'external_paths' => [
        // Add custom paths here, e.g.:
        // base_path('vendor/your-vendor/your-package/modules'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Load Composer Modules
    |--------------------------------------------------------------------------
    |
    | When enabled, the system will automatically scan vendor packages for
    | modules in their modules/ subdirectory.
    |
    */

    'load_composer_modules' => env('MODULES_LOAD_COMPOSER', false),

];