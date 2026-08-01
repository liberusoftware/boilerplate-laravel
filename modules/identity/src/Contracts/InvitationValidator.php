<?php

namespace Liberu\Foundation\Identity\Contracts;

interface InvitationValidator
{
    public function valid(string $email, ?string $token): bool;
}
