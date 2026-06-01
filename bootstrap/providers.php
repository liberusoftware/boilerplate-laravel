<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\JetstreamServiceProvider;
use App\Providers\ModularServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\SiteSettingsBridgeServiceProvider;
use App\Providers\SocialstreamServiceProvider;
use App\Providers\ThemeServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    EventServiceProvider::class,
    AdminPanelProvider::class,
    AppPanelProvider::class,
    FortifyServiceProvider::class,
    JetstreamServiceProvider::class,
    ModularServiceProvider::class,
    RouteServiceProvider::class,
    SiteSettingsBridgeServiceProvider::class,
    SocialstreamServiceProvider::class,
    ThemeServiceProvider::class,
];
