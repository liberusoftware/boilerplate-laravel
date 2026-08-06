<?php

namespace Liberu\Foundation\RolesPermissions\Services;

final class SeparationOfDuty
{
    public function permits(string|int $requester, string|int $approver, bool $material = true): bool
    {
        return ! $material || (string) $requester !== (string) $approver;
    }
}
