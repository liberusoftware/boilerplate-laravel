<?php

namespace Liberu\Foundation\Identity\Support;

use Liberu\Foundation\Identity\Contracts\RegistrationPolicy;

final readonly class ConfiguredRegistrationPolicy implements RegistrationPolicy
{
    public function __construct(private string $mode) {}

    public function permitsSelfRegistration(): bool
    {
        return in_array($this->mode, ['open', 'invitation'], true);
    }

    public function requiresInvitation(): bool
    {
        return $this->mode === 'invitation';
    }
}
