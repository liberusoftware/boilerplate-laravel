<?php

namespace App\Filament\Admin\Resources;

use Biostate\FilamentMenuBuilder\Filament\Resources\MenuResource as BaseMenuResource;

class MenuResource extends BaseMenuResource
{
    // Menus are global (no team relationship); don't tenant-scope them.
    protected static bool $isScopedToTenant = false;

    public static function getNavigationGroup(): ?string
    {
        return null;
    }
}
