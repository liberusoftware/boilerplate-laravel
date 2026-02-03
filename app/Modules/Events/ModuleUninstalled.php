<?php

namespace App\Modules\Events;

class ModuleUninstalled
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
