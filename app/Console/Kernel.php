<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * @deprecated The application now uses Application::configure() in bootstrap/app.php.
 * Schedule commands in routes/console.php using Schedule::command() directly.
 */
class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Define scheduled commands in routes/console.php
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
