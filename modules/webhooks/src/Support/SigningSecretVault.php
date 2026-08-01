<?php

namespace Liberu\Foundation\Webhooks\Support;

use Illuminate\Support\Facades\Crypt;

final class SigningSecretVault
{
    public function seal(string $secret): string
    {
        return Crypt::encryptString($secret);
    }

    public function open(string $sealed): string
    {
        return Crypt::decryptString($sealed);
    }
}
