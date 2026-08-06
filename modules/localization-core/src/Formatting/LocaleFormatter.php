<?php

namespace Liberu\Foundation\Localization\Formatting;

use DateTimeInterface;

final class LocaleFormatter
{
    public function date(DateTimeInterface $date, string $locale, string $timezone = 'UTC', int $style = \IntlDateFormatter::MEDIUM): string
    {
        $formatter = new \IntlDateFormatter($locale, $style, \IntlDateFormatter::NONE, $timezone);

        return $formatter->format($date) ?: $date->format('Y-m-d');
    }

    public function number(int|float $number, string $locale): string
    {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);

        return $formatter->format($number) ?: ((string) $number);
    }

    public function list(array $values, string $locale): string
    {
        return implode(count($values) > 2 ? ', ' : ($locale === 'en' ? ' and ' : ' '), $values);
    }
}
