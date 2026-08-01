<?php

namespace Liberu\Messaging\Core;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Liberu\Messaging\Core\Models\Message;
use Liberu\Messaging\Core\Policies\MessagePolicy;

final class MessagingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Gate::policy(Message::class, MessagePolicy::class);
    }
}
