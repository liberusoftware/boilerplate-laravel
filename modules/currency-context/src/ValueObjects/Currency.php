<?php

namespace Liberu\Foundation\Currency\ValueObjects;

use Liberu\Foundation\Currency\Exceptions\UnknownCurrency;

final readonly class Currency
{
    public string $code;

    public function __construct(string $code, public int $minorUnits, public string $symbol)
    {
        $normalized = strtoupper($code);
        if (! preg_match('/^[A-Z]{3}$/', $normalized) || $minorUnits < 0 || $minorUnits > 6) {
            throw new UnknownCurrency("Invalid currency metadata [{$code}].");
        }
        $this->code = $normalized;
    }
}
