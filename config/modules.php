<?php

$applicationModules = [
    'activity-comments', 'analytics-core', 'api-access', 'application', 'audit',
    'currency-context', 'developer-experience', 'feature-flags',
    'files-media', 'identity-core', 'identity-core-filament', 'identity-socialstream',
    'import-export', 'integrations', 'jetstream-bridge', 'localization-core', 'localization-core-livewire',
    'module-manager', 'module-manager-filament', 'notifications', 'observability',
    'organizations-teams', 'organizations-teams-filament', 'profiles', 'roles-permissions',
    'roles-permissions-filament', 'scheduler-queues', 'search', 'search-api', 'sessions-devices', 'sessions-devices-filament', 'settings',
    'settings-filament', 'theme-support', 'theme-support-livewire', 'two-factor-authentication', 'webhooks',
];

// Installed but deliberately off: each needs third-party credentials, so booting them
// out of the box would have the boilerplate reaching for services nobody configured.
// To switch one on, override both env vars — MODULES_DISABLED replaces this whole list,
// and a disabled name wins over an enabled one.
$optionalAdapters = [
    'analytics-google', 'analytics-meta', 'localization-mymemory',
];

return [
    // Composer-installed module packages. Local paths may be appended for development.
    'paths' => [base_path('modules')],

    // Runtime state is deployment configuration, distinct from installation and authorization.
    // Every directory under modules/ must appear in one of these two lists — enforced by
    // tests/Architecture/ModuleBoundariesTest.php, so an installed package can never go
    // silently unbooted.
    'enabled' => array_values(array_filter(explode(',', (string) env('MODULES_ENABLED', implode(',', $applicationModules))))),
    'disabled' => array_values(array_filter(explode(',', (string) env('MODULES_DISABLED', implode(',', $optionalAdapters))))),

    'cache' => env('MODULES_CACHE', false),
    'cache_key' => 'liberu.modules.registry.v1',
];
