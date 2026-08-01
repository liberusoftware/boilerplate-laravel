<?php

namespace Liberu\Foundation\Identity\Contracts;

interface RegistrationPolicy
{
    public function permitsSelfRegistration(): bool;

    public function requiresInvitation(): bool;
}
