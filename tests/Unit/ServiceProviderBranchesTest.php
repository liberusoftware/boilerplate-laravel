<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Liberu\Analytics\Contracts\AnalyticsDestinationRegistry;
use Liberu\Foundation\Analytics\Google\AnalyticsGoogleServiceProvider;
use Liberu\Foundation\Analytics\Google\Contracts\GoogleTransport;
use Liberu\Foundation\Analytics\Meta\AnalyticsMetaServiceProvider;
use Liberu\Foundation\Analytics\Meta\Contracts\MetaTransport;
use Liberu\Foundation\Analytics\Support\DestinationRegistry;
use Liberu\Foundation\Currency\CurrencyServiceProvider;
use Liberu\Foundation\Currency\Enums\CurrencyRole;
use Liberu\Foundation\Currency\Services\CurrencyContext;
use Liberu\Foundation\JetstreamBridge\Providers\FortifyServiceProvider;

it('registers bound Google and Meta analytics destinations', function () {
    $registry = new DestinationRegistry();
    app()->instance(AnalyticsDestinationRegistry::class, $registry);
    app()->instance(GoogleTransport::class, Mockery::mock(GoogleTransport::class));
    app()->instance(MetaTransport::class, Mockery::mock(MetaTransport::class));

    (new AnalyticsGoogleServiceProvider(app()))->boot();
    (new AnalyticsMetaServiceProvider(app()))->boot();

    expect($registry->all())->toHaveKeys(['google', 'meta']);
});

it('registers currency services and their base fallback', function () {
    config()->set('currency.currencies', [
        'USD' => ['numeric' => 840, 'minor_units' => 2, 'symbol' => '$'],
    ]);
    config()->set('currency.base', 'USD');
    config()->set('currency.display', null);
    $provider = new CurrencyServiceProvider(app());
    $provider->register();
    $provider->boot();

    expect(app(CurrencyContext::class)->for(CurrencyRole::Base)->code)->toBe('USD')
        ->and(app(CurrencyContext::class)->for(CurrencyRole::Display)->code)->toBe('USD');
});

it('evaluates Fortify password and throttle callbacks', function () {
    (new FortifyServiceProvider(app()))->boot();
    expect(Password::default())->toBeInstanceOf(Password::class);

    $request = Request::create('/login', 'POST', ['email' => ' USER@EXAMPLE.TEST ', 'credential' => ['id' => 'passkey']]);
    $request->setLaravelSession(app('session')->driver());
    $request->setUserResolver(fn () => null);

    expect(RateLimiter::limiter('login')($request))->not->toBeNull()
        ->and(RateLimiter::limiter('two-factor')($request))->not->toBeNull()
        ->and(RateLimiter::limiter('passkeys')($request))->not->toBeNull();

    $fallback = Request::create('/passkeys', 'POST');
    $fallback->setLaravelSession(app('session')->driver());
    expect(RateLimiter::limiter('passkeys')($fallback))->not->toBeNull();
});
