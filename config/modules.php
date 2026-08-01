<?php

return [
    // Composer-installed module packages. Local paths may be appended for development.
    'paths' => [base_path('modules')],

    // Runtime state is deployment configuration, distinct from installation and authorization.
    'enabled' => array_values(array_filter(explode(',', (string) env('MODULES_ENABLED', '')))),
    'disabled' => array_values(array_filter(explode(',', (string) env('MODULES_DISABLED', '')))),

    'cache' => env('MODULES_CACHE', false),
    'cache_key' => 'liberu.modules.registry.v1',
];
