<?php

namespace Liberu\Foundation\UsersRoles\Concerns;

use Liberu\Foundation\UsersRoles\Services\UserRoleLookup;

trait HasUserRoles
{
    public function authorizationIdentifier(): int|string
    {
        return $this->getKey();
    }

    public function authorizationType(): string
    {
        return $this->getMorphClass();
    }

    /** @param string|list<string> $roles */
    public function hasRoleInAnyTeam(string|array $roles): bool
    {
        return app(UserRoleLookup::class)->hasRoleInAnyTeam($this, $roles);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRoleInAnyTeam((string) config('filament-shield.super_admin.name', 'super_admin'));
    }
}
