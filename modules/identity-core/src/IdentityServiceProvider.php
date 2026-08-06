<?php

namespace Liberu\Foundation\Identity;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Liberu\Foundation\Identity\Contracts\InvitationValidator;
use Liberu\Foundation\Identity\Contracts\RegistrationPolicy;
use Liberu\Foundation\Identity\Listeners\EmitAuthenticationEvent;
use Liberu\Foundation\Identity\Support\ConfiguredRegistrationPolicy;
use Liberu\Foundation\Identity\Support\RejectingInvitationValidator;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/identity.php', 'identity');
        $this->app->singleton(RegistrationPolicy::class, fn () => new ConfiguredRegistrationPolicy((string) config('identity.registration', 'open')));
        $this->app->bind(InvitationValidator::class, RejectingInvitationValidator::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Event::listen([Failed::class, Login::class, Logout::class], EmitAuthenticationEvent::class);
        $this->publishes([__DIR__.'/../config/identity.php' => config_path('identity.php')], 'identity-config');
    }
}
