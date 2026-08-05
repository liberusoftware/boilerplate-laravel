<?php

namespace Liberu\Foundation\RolesPermissions\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class BreakGlass
{
    public function grant(string|int $actorId, string $permission, string $reason, \DateTimeImmutable $expiresAt, bool $stronglyAuthenticated): int
    {
        if (! $stronglyAuthenticated || trim($reason) === '' || $expiresAt <= new \DateTimeImmutable()) {
            throw new RuntimeException('Break-glass access requires strong authentication, reason, and future expiry.');
        }

        return DB::table('authorization_break_glass')->insertGetId(['actor_id' => (string) $actorId, 'permission' => $permission, 'reason' => $reason, 'expires_at' => $expiresAt, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function active(string|int $actorId, string $permission): bool
    {
        return DB::table('authorization_break_glass')->where('actor_id', (string) $actorId)->where('permission', $permission)->whereNull('revoked_at')->where('expires_at', '>', now())->exists();
    }
}
