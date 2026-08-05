<?php

return [
    // Composer-installed module packages. Local paths may be appended for development.
    'paths' => [base_path('modules')],

    // Which modules boot is the manifests' decision, not this file's: ModuleRegistry::resolve()
    // selects every installed module whose module.json declares default_enabled, so installing a
    // package is what offers it and its own manifest is what turns it on. The two lists below are
    // deployment overrides on top of that, empty by default.
    //
    // MODULES_ENABLED adds modules their manifests leave off — the three adapters that need
    // third-party credentials. MODULES_DISABLED removes modules their manifests turn on, and
    // wins over both the manifest and MODULES_ENABLED.
    'enabled' => array_values(array_filter(explode(',', (string) env('MODULES_ENABLED', '')))),
    'disabled' => array_values(array_filter(explode(',', (string) env('MODULES_DISABLED', '')))),

    'cache' => env('MODULES_CACHE', false),
    'cache_key' => 'liberu.modules.registry.v1',
];
