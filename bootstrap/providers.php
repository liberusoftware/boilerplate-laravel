<?php

use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AppPanelProvider;
use Liberu\Foundation\ModuleManager\ModuleManagerServiceProvider;

return [
    ModuleManagerServiceProvider::class,
    AdminPanelProvider::class,
    AppPanelProvider::class,
];
