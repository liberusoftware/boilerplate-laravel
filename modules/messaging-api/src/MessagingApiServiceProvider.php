<?php

namespace Liberu\Messaging\Api;

use Illuminate\Support\ServiceProvider;

final class MessagingApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
