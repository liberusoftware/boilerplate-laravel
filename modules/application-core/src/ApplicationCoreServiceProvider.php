<?php

namespace Liberu\Foundation\ApplicationCore;

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\ApplicationCore\Contracts\Clock;
use Liberu\Foundation\ApplicationCore\Contracts\IdentifierFactory;
use Liberu\Foundation\ApplicationCore\Health\ReadinessRegistry;
use Liberu\Foundation\ApplicationCore\Support\EnvironmentValidator;
use Liberu\Foundation\ApplicationCore\Support\SystemClock;
use Liberu\Foundation\ApplicationCore\Support\UuidIdentifierFactory;

final class ApplicationCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/application-core.php', 'application-core');
        $this->app->singleton(Clock::class, SystemClock::class);
        $this->app->singleton(IdentifierFactory::class, UuidIdentifierFactory::class);
        $this->app->singleton(ReadinessRegistry::class);
    }

    public function boot(EnvironmentValidator $validator): void
    {
        $validator->validate();
        $this->loadRoutesFrom(__DIR__.'/../routes/health.php');
        $this->publishes([__DIR__.'/../config/application-core.php' => config_path('application-core.php')], 'application-core-config');
    }
}
