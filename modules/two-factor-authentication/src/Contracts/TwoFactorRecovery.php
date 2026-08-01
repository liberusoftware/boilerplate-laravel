<?php

namespace Liberu\Foundation\TwoFactor\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface TwoFactorRecovery
{
    public function recover(Authenticatable $subject, Authenticatable $administrator, string $reason): void;
}
