<?php

namespace Liberu\Foundation\ActivityComments\Contracts;

interface ActivityAuthorizer
{
    public function allowed(string|int $actorId, string $subjectType, string|int $subjectId, string $operation): bool;
}
