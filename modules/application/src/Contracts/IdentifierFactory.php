<?php

namespace Liberu\Foundation\ApplicationCore\Contracts;

interface IdentifierFactory
{
    public function make(): string;
}
