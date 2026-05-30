<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(headers:
            \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->trimStrings(except: [
            'current_password',
            'password',
            'password_confirmation',
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'auth'            => \App\Http\Middleware\Authenticate::class,
            'auth.session'    => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers'   => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can'             => \Illuminate\Auth\Middleware\Authorize::class,
            'guest'           => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'locale'          => \App\Http\Middleware\SetLocale::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'precognitive'    => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            'signed'          => \App\Http\Middleware\ValidateSignature::class,
            'throttle'        => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified'        => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);
    })
    ->create();
