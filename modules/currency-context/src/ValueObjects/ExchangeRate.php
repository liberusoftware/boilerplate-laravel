<?php

namespace Liberu\Foundation\Currency\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ExchangeRate
{
    public function __construct(
        public Currency $base,
        public Currency $quote,
        public string $rate,
        public string $source,
        public string $type,
        public DateTimeImmutable $effectiveAt,
        public int $precision,
        public bool $inverted = false,
    ) {
        if (! preg_match('/^\d+(?:\.\d+)?$/', $rate) || ! preg_match('/[1-9]/', $rate) || $base->code === $quote->code) {
            throw new InvalidArgumentException('An exchange rate must be positive and relate distinct currencies.');
        }
    }

    public function isStale(DateTimeImmutable $at, int $maximumAgeSeconds): bool
    {
        return ($at->getTimestamp() - $this->effectiveAt->getTimestamp()) > $maximumAgeSeconds;
    }
}
