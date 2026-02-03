<?php

namespace App\Modules\Events;

class ModuleInstalled
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
