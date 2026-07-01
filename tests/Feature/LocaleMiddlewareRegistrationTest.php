<?php

use App\Http\Middleware\SetLocale;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\App;

it('applies a request locale through the registered web middleware group', function () {
    $this->get('/?locale=es')->assertOk();

    expect(App::getLocale())->toBe('es')
        ->and(session('locale'))->toBe('es');
});

it('registers SetLocale on both Filament panels so the authenticated surface localizes', function () {
    expect(Filament::getPanel('admin')->getMiddleware())->toContain(SetLocale::class)
        ->and(Filament::getPanel('app')->getMiddleware())->toContain(SetLocale::class);
});
