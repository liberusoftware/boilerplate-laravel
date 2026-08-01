<?php

namespace Liberu\Foundation\Currency\Services;

use Liberu\Foundation\Currency\Enums\CurrencyRole;
use Liberu\Foundation\Currency\ValueObjects\Currency;

final class CurrencyContext
{
    /** @param array<string, Currency> $currencies */
    public function __construct(private array $currencies) {}

    public function for(CurrencyRole $role): Currency
    {
        return $this->currencies[$role->value] ?? $this->currencies[CurrencyRole::Base->value];
    }
}
