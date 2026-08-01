<?php

namespace Liberu\Foundation\Analytics\Google\Contracts;

interface GoogleTransport
{
    public function send(array $payload): array;
}
