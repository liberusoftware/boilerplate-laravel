<?php

namespace App\Filament\Admin\Resources;

use Biostate\FilamentMenuBuilder\Filament\Resources\MenuItemResource as BaseMenuItemResource;

class MenuItemResource extends BaseMenuItemResource
{
    protected static bool $shouldRegisterNavigation = false;

    // Menu items are global (no team relationship); don't tenant-scope them.
    protected static bool $isScopedToTenant = false;
}
