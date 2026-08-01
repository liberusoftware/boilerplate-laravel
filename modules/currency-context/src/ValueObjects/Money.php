<?php

namespace Liberu\Foundation\Currency\ValueObjects;

use Liberu\Foundation\Currency\Exceptions\CurrencyMismatch;

final readonly class Money
{
    public function __construct(public int $minorAmount, public Currency $currency) {}

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorAmount + $other->minorAmount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorAmount - $other->minorAmount, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->currency->code === $other->currency->code && $this->minorAmount === $other->minorAmount;
    }

    public function decimal(): string
    {
        if ($this->currency->minorUnits === 0) {
            return (string) $this->minorAmount;
        }
        $negative = $this->minorAmount < 0;
        $digits = str_pad((string) abs($this->minorAmount), $this->currency->minorUnits + 1, '0', STR_PAD_LEFT);
        $value = substr($digits, 0, -$this->currency->minorUnits).'.'.substr($digits, -$this->currency->minorUnits);

        return $negative ? '-'.$value : $value;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency->code !== $other->currency->code) {
            throw new CurrencyMismatch("Cannot combine {$this->currency->code} and {$other->currency->code}.");
        }
    }
}
