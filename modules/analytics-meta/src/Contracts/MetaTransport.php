<?php

namespace Liberu\Analytics\Meta\Contracts;

interface MetaTransport
{
    public function send(array $payload): array;
}
