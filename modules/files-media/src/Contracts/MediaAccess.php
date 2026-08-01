<?php

namespace Liberu\Foundation\Files\Contracts;

interface MediaAccess
{
    public function authorized(string|int|null $actorId, string $mediaId, string $operation): bool;
}
