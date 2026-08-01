<?php

namespace Liberu\Foundation\Localization\Context;

final readonly class LocaleContext
{
    public function __construct(public string $locale, public string $timezone, public string $direction) {}

    public function payload(): array
    {
        return ['locale' => $this->locale, 'timezone' => $this->timezone];
    }
}
