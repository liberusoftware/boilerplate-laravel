<?php

namespace Liberu\Foundation\Identity\Support;

final class IdentifierNormalizer
{
    public function email(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
