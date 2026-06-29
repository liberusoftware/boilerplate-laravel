<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\SocialstreamServiceProvider;
use App\Providers\TelescopeServiceProvider;
use App\Providers\ThemeServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    AppPanelProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
    SocialstreamServiceProvider::class,
    TelescopeServiceProvider::class,
    ThemeServiceProvider::class,
];
