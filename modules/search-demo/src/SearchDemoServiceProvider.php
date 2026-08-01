<?php

namespace Liberu\Search\Demo;

use Illuminate\Support\ServiceProvider;

final class SearchDemoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config()->set('search.models.post', Models\Post::class);
        config()->set('search.models.group', Models\Group::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
