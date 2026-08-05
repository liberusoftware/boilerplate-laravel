<?php

namespace Liberu\Foundation\RolesPermissions\Registry;

use InvalidArgumentException;

final class PermissionRegistry
{
    private array $permissions = [];

    public function declare(string $permission, string $owner, string $description): void
    {
        if (! preg_match('/^[a-z0-9-]+\.[a-z0-9-]+\.[a-z0-9-]+$/', $permission)) {
            throw new InvalidArgumentException('Permission must use {module}.{resource}.{action}.');
        }if (isset($this->permissions[$permission])) {
            throw new InvalidArgumentException("Permission [{$permission}] is already declared.");
        }$this->permissions[$permission] = compact('owner', 'description');
    }

    public function all(): array
    {
        return $this->permissions;
    }
}
