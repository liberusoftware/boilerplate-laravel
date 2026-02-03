<?php

namespace App\Modules\Events;

class ModuleDisabled
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
