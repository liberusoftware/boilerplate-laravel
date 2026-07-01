<?php

use Illuminate\Console\Scheduling\Schedule;

it('schedules the database backup and cleanup commands', function () {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn ($event) => $event->command ?? '')
        ->values();

    expect($commands->contains(fn (string $c) => str_contains($c, 'backup:run')))->toBeTrue();
    expect($commands->contains(fn (string $c) => str_contains($c, 'backup:clean')))->toBeTrue();
});
