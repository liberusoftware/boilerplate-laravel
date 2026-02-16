<?php

namespace App\Modules\Events;

use App\Modules\Contracts\ModuleInterface;

class ModuleUninstalled
{
    public string $name;
    public ModuleInterface $module;

    public function __construct(string $name, ModuleInterface $module)
    {
        $this->name = $name;
        $this->module = $module;
    }
}
