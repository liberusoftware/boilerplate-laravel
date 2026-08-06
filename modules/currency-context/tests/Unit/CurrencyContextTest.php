<?php

use Liberu\Foundation\Currency\Exceptions\CurrencyMismatch;
use Liberu\Foundation\Currency\Services\CurrencyRegistry;
use Liberu\Foundation\Currency\ValueObjects\Money;

it('keeps monetary amounts precise in minor units', function () {
    $registry = app(CurrencyRegistry::class);
    $usd = $registry->get('USD');

    expect((new Money(12345, $usd))->decimal())->toBe('123.45')
        ->and((new Money(-5, $usd))->decimal())->toBe('-0.05')
        ->and((new Money(100, $usd))->add(new Money(25, $usd))->minorAmount)->toBe(125);
});

it('rejects arithmetic across currencies', function () {
    $registry = app(CurrencyRegistry::class);

    expect(fn () => (new Money(100, $registry->get('USD')))->add(new Money(100, $registry->get('EUR'))))
        ->toThrow(CurrencyMismatch::class);
});
