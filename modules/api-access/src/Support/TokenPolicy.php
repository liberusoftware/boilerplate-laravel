<?php

namespace Liberu\Foundation\ApiAccess\Support;

use InvalidArgumentException;

final class TokenPolicy
{
    /** @param list<string> $requested @param list<string> $allowed @return list<string> */
    public function scopes(array $requested, array $allowed): array
    {
        $invalid = array_diff($requested, $allowed);
        if ($invalid !== []) {
            throw new InvalidArgumentException('Requested token scope is not permitted.');
        }

        return array_values(array_unique($requested));
    }

    public function expiresAt(?\DateTimeImmutable $requested = null): \DateTimeImmutable
    {
        $maximum = new \DateTimeImmutable('+'.config('api-access.maximum_token_days', 90).' days');

        return $requested === null || $requested > $maximum ? $maximum : $requested;
    }
}
