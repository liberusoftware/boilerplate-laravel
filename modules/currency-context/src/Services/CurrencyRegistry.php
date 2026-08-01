<?php

namespace Liberu\Foundation\Currency\Services;

use Liberu\Foundation\Currency\Exceptions\UnknownCurrency;
use Liberu\Foundation\Currency\ValueObjects\Currency;

final class CurrencyRegistry
{
    /** @param array<string, array{minor_units: int, symbol: string}> $definitions */
    public function __construct(private array $definitions) {}

    public function get(string $code): Currency
    {
        $code = strtoupper($code);
        $definition = $this->definitions[$code] ?? null;
        if ($definition === null) {
            throw new UnknownCurrency("Currency [{$code}] is not configured.");
        }

        return new Currency($code, $definition['minor_units'], $definition['symbol']);
    }
}
