<?php

namespace Liberu\Foundation\Search;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\Search\Console\ReindexCommand;
use Liberu\Foundation\Search\Contracts\SearchIndexer;
use Liberu\Foundation\Search\Registry\IndexableRegistry;
use Liberu\Foundation\Search\Registry\SearcherRegistry;
use Liberu\Foundation\Search\Services\LocalSearchIndexer;
use Liberu\Foundation\Search\Services\SearchService;

final class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/search.php', 'search');
        config()->set('search.models.user', config('search.models.user') ?? config('auth.providers.users.model'));
        $this->app->singleton(IndexableRegistry::class);
        $this->app->singleton(SearcherRegistry::class);
        $this->app->bind(SearchIndexer::class, LocalSearchIndexer::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $registry = $this->app->make(IndexableRegistry::class);
        foreach ((array) config('search.models') as $type => $model) {
            if (is_string($model)) {
                $registry->register($type, $model);
            }
        }
        // Deferred through the container so registering a searcher costs no
        // SearchService instantiation for a request that never searches.
        $this->app->make(SearcherRegistry::class)->register(
            'users',
            fn (array $filters): LengthAwarePaginator => $this->app->make(SearchService::class)->searchUsers($filters),
        );
        if ($this->app->runningInConsole()) {
            $this->commands([ReindexCommand::class]);
        }
        $this->publishes([__DIR__.'/../config/search.php' => config_path('search.php')], 'search-config');
    }
}
