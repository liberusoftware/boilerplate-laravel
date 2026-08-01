<?php

namespace Liberu\Foundation\TwoFactor\Enforcement;

use Illuminate\Contracts\Auth\Authenticatable;

final class TwoFactorPolicy
{
    /** @param list<string> $roles */
    public function requiredFor(Authenticatable $actor, array $roles = []): bool
    {
        return (bool) config('two-factor.enforce_all', false)
            || array_intersect((array) config('two-factor.required_roles', []), $roles) !== [];
    }
}
