<?php

namespace Liberu\Foundation\ApplicationCore\Health;

interface ReadinessCheck
{
    public function name(): string;

    public function ready(): bool;
}
