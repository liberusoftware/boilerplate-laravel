<?php

namespace Liberu\Foundation\Currency\Contracts;

use DateTimeImmutable;
use Liberu\Foundation\Currency\ValueObjects\Currency;
use Liberu\Foundation\Currency\ValueObjects\ExchangeRate;

interface ExchangeRateProvider
{
    public function rate(Currency $base, Currency $quote, DateTimeImmutable $effectiveAt): ?ExchangeRate;
}
