<?php

namespace Liberu\Foundation\Currency;

use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\Currency\Enums\CurrencyRole;
use Liberu\Foundation\Currency\Services\CurrencyContext;
use Liberu\Foundation\Currency\Services\CurrencyRegistry;

final class CurrencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/currency.php', 'currency');
        $this->app->singleton(CurrencyRegistry::class, fn () => new CurrencyRegistry(config('currency.currencies', [])));
        $this->app->scoped(CurrencyContext::class, function ($app) {
            $registry = $app->make(CurrencyRegistry::class);
            $base = $registry->get(config('currency.base'));
            $display = config('currency.display');

            return new CurrencyContext([
                CurrencyRole::Base->value => $base,
                CurrencyRole::Display->value => $display ? $registry->get($display) : $base,
            ]);
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([__DIR__.'/../config/currency.php' => config_path('currency.php')], 'currency-config');
    }
}
