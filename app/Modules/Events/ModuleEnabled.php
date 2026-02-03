<?php

namespace App\Modules\Events;

class ModuleEnabled
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
