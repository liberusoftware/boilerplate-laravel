<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Modules Directory Name
    |--------------------------------------------------------------------------
    |
    | Define the directory name for the modules. This will be used to
    | generate the modules path. Default: "app-modules"
    |
    */
    'modules_directory' => 'app-modules',

    /*
    |--------------------------------------------------------------------------
    | Modules Namespace
    |--------------------------------------------------------------------------
    |
    | Define the base namespace for the modules. Default: "Modules"
    |
    */
    'modules_namespace' => 'Modules',

    /*
    |--------------------------------------------------------------------------
    | Auto Discovery
    |--------------------------------------------------------------------------
    |
    | When enabled, the module system will automatically discover and register
    | modules found in the modules directory.
    |
    */
    'auto_discovery' => env('MODULAR_AUTO_DISCOVERY', true),

    /*
    |--------------------------------------------------------------------------
    | Generate IDE Helper
    |--------------------------------------------------------------------------
    |
    | When enabled, the module system will generate an IDE helper file
    | to assist with code completion and navigation.
    |
    */
    'generate_ide_helper' => env('MODULAR_IDE_HELPER', false),

    /*
    |--------------------------------------------------------------------------
    | Custom Theme Support
    |--------------------------------------------------------------------------
    |
    | Enable custom theme support for modules. Modules can provide their own
    | themes and assets.
    |
    */
    'theme_support' => true,

    /*
    |--------------------------------------------------------------------------
    | Theme Directory
    |--------------------------------------------------------------------------
    |
    | Define the directory for themes. Default: "themes"
    |
    */
    'theme_directory' => 'themes',

    /*
    |--------------------------------------------------------------------------
    | Module Composer
    |--------------------------------------------------------------------------
    |
    | When enabled, each module will have its own composer.json file
    | and will be treated as a composer package.
    |
    */
    'module_composer' => true,

    /*
    |--------------------------------------------------------------------------
    | Cache Modules
    |--------------------------------------------------------------------------
    |
    | When enabled, module information will be cached to improve performance.
    | This is recommended for production environments.
    |
    */
    'cache' => env('MODULAR_CACHE', !env('APP_DEBUG', false)),

    /*
    |--------------------------------------------------------------------------
    | Cache Key
    |--------------------------------------------------------------------------
    |
    | The cache key used to store module information.
    |
    */
    'cache_key' => 'modular.modules',

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
    | Testing
    |--------------------------------------------------------------------------
    |
    | Configure module testing settings.
    |
    */
    'testing' => [
        'enabled' => true,
        'parallel' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Support
    |--------------------------------------------------------------------------
    |
    | Enable Filament 5 integration for modules. Modules can provide their own
    | Filament resources, pages, and widgets.
    |
    */
    'filament' => [
        'enabled' => true,
        'version' => 5,
        'auto_discover_resources' => true,
        'auto_discover_pages' => true,
        'auto_discover_widgets' => true,
    ],
];
