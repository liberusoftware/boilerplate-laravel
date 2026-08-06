<?php

namespace Liberu\Analytics\Core\Support;

final class EventSanitizer
{
    /** @param array<string,mixed> $properties @param list<string> $allowed */
    public function sanitize(array $properties, array $allowed): array
    {
        return array_intersect_key($properties, array_flip($allowed));
    }

    public function pseudonymize(?string $identifier, string $salt): ?string
    {
        return $identifier === null ? null : hash_hmac('sha256', trim(mb_strtolower($identifier)), $salt);
    }
}
