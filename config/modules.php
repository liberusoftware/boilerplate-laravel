<?php

$enabledModules = [
    'activity-comments', 'analytics-core', 'api-access', 'application-core', 'audit',
    'blog-core', 'blog-filament', 'currency-context', 'developer-experience', 'feature-flags',
    'files-media', 'foundation-filament', 'identity', 'identity-filament', 'identity-socialstream',
    'import-export', 'integrations', 'jetstream-bridge', 'localization', 'localization-livewire',
    'messaging', 'messaging-api', 'messaging-filament', 'module-manager', 'notifications',
    'observability', 'organizations-teams', 'organizations-teams-filament', 'profiles',
    'roles-permissions', 'roles-permissions-filament', 'scheduler-queues', 'search', 'search-api',
    'sessions-devices', 'settings', 'settings-filament', 'theme-support',
    'two-factor-authentication', 'webhooks',
];

$disabledModules = [
    'analytics-google',
    'analytics-meta',
    'localization-mymemory',
    'search-demo',
];

return [
    'available' => [
        'enabled' => $enabledModules,
        'disabled' => $disabledModules,
    ],

    // Composer-installed module packages. Local paths may be appended for development.
    'paths' => [base_path('modules')],

    // Runtime state is deployment configuration, distinct from installation and authorization.
    'enabled' => array_values(array_unique(array_merge(
        $enabledModules,
        array_filter(explode(',', (string) env('MODULES_ENABLED', ''))),
    ))),
    'disabled' => array_values(array_filter(explode(',', (string) env('MODULES_DISABLED', implode(',', $disabledModules))))),

    'cache' => env('MODULES_CACHE', false),
    'cache_key' => 'liberu.modules.registry.v1',
];
