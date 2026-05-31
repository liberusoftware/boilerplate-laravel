<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Biostate\FilamentMenuBuilder\Models\Menu as BaseMenu;

class Menu extends BaseMenu
{
    use IsTenantModel;
}
