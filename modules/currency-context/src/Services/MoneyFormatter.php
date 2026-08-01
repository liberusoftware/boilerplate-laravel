<?php

namespace Liberu\Foundation\Currency\Services;

use Liberu\Foundation\Currency\ValueObjects\Money;

final class MoneyFormatter
{
    public function format(Money $money, string $locale): string
    {
        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
            $formatted = $formatter->formatCurrency((float) $money->decimal(), $money->currency->code);
            if (is_string($formatted)) {
                return $formatted;
            }
        }

        return $money->currency->code.' '.$money->decimal();
    }
}
