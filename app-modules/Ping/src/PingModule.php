<?php

namespace Modules\Ping;

class PingModule
{
    /**
     * Get the module name.
     */
    public static function getName(): string
    {
        return 'Ping';
    }

    /**
     * Get the module version.
     */
    public static function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Get the module description.
     */
    public static function getDescription(): string
    {
        return 'Ping Module';
    }
}
