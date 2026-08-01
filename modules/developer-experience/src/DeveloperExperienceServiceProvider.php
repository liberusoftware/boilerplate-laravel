<?php

namespace Liberu\Foundation\DeveloperExperience;

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\DeveloperExperience\Console\FoundationDoctorCommand;

final class DeveloperExperienceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([FoundationDoctorCommand::class]);
        }
    }
}
