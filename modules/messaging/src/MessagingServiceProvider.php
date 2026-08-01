<?php

namespace Liberu\Messaging\Core;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Messaging\Core\Contracts\Messaging;
use Liberu\Messaging\Core\Models\Message;
use Liberu\Messaging\Core\Policies\MessagePolicy;
use Liberu\Messaging\Core\Services\EloquentMessaging;

final class MessagingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Messaging::class, EloquentMessaging::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Message::class, MessagePolicy::class);
    }
}
