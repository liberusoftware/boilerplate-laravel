<?php

return [
    'paths' => [base_path('modules')],
    'enabled' => array_values(array_filter(explode(',', (string) env('MODULES_ENABLED', '')))),
    'disabled' => array_values(array_filter(explode(',', (string) env('MODULES_DISABLED', '')))),
    'cache' => (bool) env('MODULES_CACHE', false),
    'cache_path' => base_path('bootstrap/cache/liberu-modules.php.cache'),
];
