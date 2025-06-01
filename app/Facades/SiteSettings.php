<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class SiteSettings extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'siteconfig';
    }
}
