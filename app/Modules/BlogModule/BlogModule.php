<?php

namespace App\Modules\BlogModule;

use Log;
use App\Modules\BaseModule;

class BlogModule extends BaseModule
{
    protected function onEnable(): void
    {
        // Called when module is enabled
        Log::info('Blog module has been enabled');
    }

    protected function onDisable(): void
    {
        // Called when module is disabled
        Log::info('Blog module has been disabled');
    }

    protected function onInstall(): void
    {
        // Called when module is installed
        Log::info('Blog module has been installed');
    }

    protected function onUninstall(): void
    {
        // Called when module is uninstalled
        Log::info('Blog module has been uninstalled');
    }
}