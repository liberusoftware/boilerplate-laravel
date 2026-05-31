<?php

namespace App\Models;

use App\Traits\IsTenantModel;
use Biostate\FilamentMenuBuilder\Models\MenuItem as BaseMenuItem;

class MenuItem extends BaseMenuItem
{
    use IsTenantModel;
}
