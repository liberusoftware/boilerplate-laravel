<?php

$applicationModules = [
    'activity-comments', 'analytics-core', 'api-access', 'application-core', 'audit',
    'blog-core', 'blog-filament', 'currency-context', 'developer-experience', 'feature-flags',
    'files-media', 'foundation-filament', 'identity', 'identity-filament', 'identity-socialstream',
    'import-export', 'integrations', 'jetstream-bridge', 'localization', 'localization-livewire', 'messaging',
    'messaging-api', 'messaging-filament', 'module-manager', 'notifications', 'observability',
    'organizations-teams', 'organizations-teams-filament', 'profiles', 'roles-permissions',
    'roles-permissions-filament', 'scheduler-queues', 'search', 'search-api', 'search-demo', 'sessions-devices', 'settings',
    'settings-filament', 'theme-support', 'two-factor-authentication', 'webhooks',
];

return [
    // Composer-installed module packages. Local paths may be appended for development.
    'paths' => [base_path('modules')],

    // Runtime state is deployment configuration, distinct from installation and authorization.
    'enabled' => array_values(array_filter(explode(',', (string) env('MODULES_ENABLED', implode(',', $applicationModules))))),
    'disabled' => array_values(array_filter(explode(',', (string) env('MODULES_DISABLED', '')))),

    'cache' => env('MODULES_CACHE', false),
    'cache_key' => 'liberu.modules.registry.v1',
];
