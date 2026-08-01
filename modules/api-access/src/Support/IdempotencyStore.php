<?php

namespace Liberu\Foundation\ApiAccess\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class IdempotencyStore
{
    public function begin(string $identity, string $key, string $requestBody): ?object
    {
        $hash = hash('sha256', $requestBody);
        $existing = DB::table('api_idempotency_keys')->where('identity_ref', $identity)->where('key', $key)->where('expires_at', '>', now())->first();
        if ($existing && ! hash_equals($existing->request_hash, $hash)) {
            throw new RuntimeException('Idempotency key was reused with a different request.');
        }if ($existing) {
            return $existing;
        }DB::table('api_idempotency_keys')->insert(['identity_ref' => $identity, 'key' => $key, 'request_hash' => $hash, 'expires_at' => now()->addHours((int) config('api-access.idempotency_hours', 24)), 'created_at' => now(), 'updated_at' => now()]);

        return null;
    }

    public function complete(string $identity, string $key, int $status, string $body): void
    {
        DB::table('api_idempotency_keys')->where('identity_ref', $identity)->where('key', $key)->update(['response_status' => $status, 'response_body' => $body, 'updated_at' => now()]);
    }
}
