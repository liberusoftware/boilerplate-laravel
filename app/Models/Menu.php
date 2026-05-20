<?php
namespace App\Models;

use App\Traits\IsTenantModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Biostate\FilamentMenuBuilder\Models\Menu as BaseMenu;

class Menu extends BaseMenu
{
    use IsTenantModel;
}
