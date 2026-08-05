<?php

namespace Liberu\Analytics\Google\Contracts;

interface GoogleTransport
{
    public function send(array $payload): array;
}
