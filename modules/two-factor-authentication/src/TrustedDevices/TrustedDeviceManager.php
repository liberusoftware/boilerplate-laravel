<?php

namespace Liberu\Foundation\TwoFactor\TrustedDevices;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TrustedDeviceManager
{
    public function issue(string|int $actorId, ?string $label = null): string
    {
        $selector = Str::random(16);
        $secret = Str::random(64);
        DB::table('two_factor_trusted_devices')->insert(['actor_id' => (string) $actorId, 'selector' => $selector, 'secret_hash' => hash('sha256', $secret), 'label' => $label, 'last_used_at' => now(), 'expires_at' => now()->addDays((int) config('two-factor.trusted_device_days', 30)), 'created_at' => now(), 'updated_at' => now()]);

        return $selector.'.'.$secret;
    }

    public function valid(string|int $actorId, string $credential): bool
    {
        [$selector,$secret] = array_pad(explode('.', $credential, 2), 2, '');
        $device = DB::table('two_factor_trusted_devices')->where('actor_id', (string) $actorId)->where('selector', $selector)->whereNull('revoked_at')->where('expires_at', '>', now())->first();
        if (! $device || ! hash_equals((string) $device->secret_hash, hash('sha256', $secret))) {
            return false;
        }DB::table('two_factor_trusted_devices')->where('id', $device->id)->update(['last_used_at' => now(), 'updated_at' => now()]);

        return true;
    }

    public function revokeAll(string|int $actorId): int
    {
        return DB::table('two_factor_trusted_devices')->where('actor_id', (string) $actorId)->whereNull('revoked_at')->update(['revoked_at' => now(), 'updated_at' => now()]);
    }
}
