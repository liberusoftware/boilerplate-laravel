<?php

namespace Liberu\Foundation\Integrations\Support;

use Illuminate\Support\Facades\Crypt;

final class CredentialVault
{
    public function seal(array $credentials): string
    {
        return Crypt::encryptString(json_encode($credentials, JSON_THROW_ON_ERROR));
    }

    public function open(string $sealed): array
    {
        return json_decode(Crypt::decryptString($sealed), true, flags: JSON_THROW_ON_ERROR);
    }
}
