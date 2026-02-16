<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

test('set locale middleware sets locale from request parameter', function () {
    $request = Request::create('/test', 'GET', ['locale' => 'es']);
    $middleware = new SetLocale();
    
    $middleware->handle($request, function ($req) {
        expect(App::getLocale())->toBe('es');
        expect(Session::get('locale'))->toBe('es');
        return response('OK');
    });
});

test('set locale middleware sets locale from session', function () {
    Session::put('locale', 'fr');
    
    $request = Request::create('/test', 'GET');
    $middleware = new SetLocale();
    
    $middleware->handle($request, function ($req) {
        expect(App::getLocale())->toBe('fr');
        return response('OK');
    });
    
    Session::forget('locale');
});

test('set locale middleware validates supported locales', function () {
    $request = Request::create('/test', 'GET', ['locale' => 'invalid']);
    $middleware = new SetLocale();
    
    $middleware->handle($request, function ($req) {
        // Should fallback to default locale
        expect(App::getLocale())->toBe('en');
        return response('OK');
    });
});

test('set locale middleware detects locale from accept language header', function () {
    $request = Request::create('/test', 'GET');
    $request->headers->set('Accept-Language', 'es-ES,es;q=0.9,en;q=0.8');
    
    $middleware = new SetLocale();
    
    $middleware->handle($request, function ($req) {
        expect(App::getLocale())->toBe('es');
        return response('OK');
    });
});

test('set locale middleware uses default locale when no preference found', function () {
    $request = Request::create('/test', 'GET');
    $middleware = new SetLocale();
    
    $middleware->handle($request, function ($req) {
        expect(App::getLocale())->toBe('en');
        return response('OK');
    });
});
