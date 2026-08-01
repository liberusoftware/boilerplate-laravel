<?php

namespace Liberu\Foundation\ImportExport;

use Illuminate\Support\ServiceProvider;

final class ImportExportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
