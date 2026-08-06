<?php

namespace Liberu\Foundation\RolesPermissions\Contracts;

interface PrivilegedActor
{
    public function authorizationIdentifier(): int|string;

    public function authorizationType(): string;

    /** @param string|list<string> $roles */
    public function hasRoleInAnyTeam(string|array $roles): bool;

    public function isSuperAdmin(): bool;
}
