<?php

namespace Liberu\Foundation\Currency\Services;

use Closure;
use Liberu\Foundation\Currency\ValueObjects\Money;

final class MoneyFormatter
{
    /** @param null|Closure(string): object $formatterFactory */
    public function __construct(private readonly ?Closure $formatterFactory = null) {}

    public function format(Money $money, string $locale): string
    {
        if (class_exists(\NumberFormatter::class)) {
            $formatter = $this->formatterFactory
                ? ($this->formatterFactory)($locale)
                : new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
            $formatted = $formatter->formatCurrency((float) $money->decimal(), $money->currency->code);
            if (is_string($formatted)) {
                return $formatted;
            }
        }

        return $money->currency->code.' '.$money->decimal();
    }
}
