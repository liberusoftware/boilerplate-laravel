<?php

namespace Liberu\Foundation\ApplicationCore\Support;

use Illuminate\Support\Str;
use Liberu\Foundation\ApplicationCore\Contracts\IdentifierFactory;

final class UuidIdentifierFactory implements IdentifierFactory
{
    public function make(): string
    {
        return (string) Str::uuid();
    }
}
