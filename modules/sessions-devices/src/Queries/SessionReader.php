<?php

namespace Liberu\Foundation\Sessions\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SessionReader
{
    /** @return Collection<int, object> */
    public function forActor(string|int $actorId, ?string $currentId = null): Collection
    {
        return DB::table(config('sessions-devices.table', 'sessions'))
            ->where('user_id', $actorId)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(function (object $session) use ($currentId): object {
                $session->is_current = hash_equals((string) $session->id, (string) $currentId);
                $session->ip_address = $this->summarizeIp($session->ip_address);

                return $session;
            });
    }

    public function revoke(string|int $actorId, string $sessionId, ?string $currentId = null): bool
    {
        if ($currentId !== null && hash_equals($sessionId, $currentId)) {
            return false;
        }

        return DB::table(config('sessions-devices.table', 'sessions'))
            ->where('user_id', $actorId)->where('id', $sessionId)->delete() === 1;
    }

    public function revokeOthers(string|int $actorId, string $currentId): int
    {
        return DB::table(config('sessions-devices.table', 'sessions'))
            ->where('user_id', $actorId)->where('id', '!=', $currentId)->delete();
    }

    private function summarizeIp(?string $ip): ?string
    {
        if ($ip === null) {
            return null;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }

        return preg_replace('/:[^:]+$/', ':0', $ip);
    }
}
