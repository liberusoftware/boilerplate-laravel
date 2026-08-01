<?php

namespace Liberu\Foundation\Integrations\Contracts;

interface IntegrationAdapter
{
    public function name(): string;

    public function capabilities(): array;

    public function test(array $credentials): bool;
}
