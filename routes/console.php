<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly database backup, prune old archives first.
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run', ['--only-db'])->daily()->at('01:30');
