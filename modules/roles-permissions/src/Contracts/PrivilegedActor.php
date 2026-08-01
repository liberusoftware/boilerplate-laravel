<?php

namespace Liberu\Foundation\Authorization\Contracts;

interface PrivilegedActor
{
    public function authorizationIdentifier(): int|string;

    public function authorizationType(): string;

    public function isSuperAdmin(): bool;
}
