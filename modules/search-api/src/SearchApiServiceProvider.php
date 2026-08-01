<?php

namespace Liberu\Foundation\SearchApi;

use Illuminate\Support\ServiceProvider;

final class SearchApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
