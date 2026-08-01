<?php

namespace Liberu\Foundation\ApplicationCore\Contracts;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
