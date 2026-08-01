<?php

namespace Liberu\Foundation\Authorization\Contracts;

interface PrivilegedActor
{
    public function isSuperAdmin(): bool;
}
