<?php

namespace App\Modules\BlogModule\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\BlogModule\Services\BlogService;

class BlogModuleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register blog service
        $this->app->singleton(BlogService::class, function ($app) {
            return new BlogService();
        });

        // Merge module configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/blog.php', 'blog');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load module routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Load module views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'blog');

        // Load module migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Publish module assets
        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('modules/blog'),
        ], 'blog-assets');

        // Publish module configuration
        $this->publishes([
            __DIR__ . '/../config/blog.php' => config_path('blog.php'),
        ], 'blog-config');
    }
}