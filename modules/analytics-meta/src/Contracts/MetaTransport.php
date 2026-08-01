<?php

namespace Liberu\Foundation\Analytics\Meta\Contracts;

interface MetaTransport
{
    public function send(array $payload): array;
}
