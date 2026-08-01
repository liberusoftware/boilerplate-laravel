<?php

namespace Liberu\Foundation\SchedulerQueues;

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;

final class SchedulerQueuesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Schedule::command('backup:clean')->daily()->at('01:00')->onOneServer();
        Schedule::command('backup:run', ['--only-db'])->daily()->at('01:30')->onOneServer()->withoutOverlapping();
    }
}
