<?php

namespace Liberu\Foundation\Identity\Support;

use Liberu\Foundation\Identity\Contracts\InvitationValidator;

final class RejectingInvitationValidator implements InvitationValidator
{
    public function valid(string $email, ?string $token): bool
    {
        return false;
    }
}
