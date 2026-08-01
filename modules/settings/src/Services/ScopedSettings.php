<?php

namespace Liberu\Foundation\Settings\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Liberu\Foundation\Settings\Contracts\SettingDefinition;

final class ScopedSettings
{
    public function put(SettingDefinition $definition, string $scopeType, string $scopeId, mixed $value): void
    {
        if (! $definition->validate($value)) {
            throw new InvalidArgumentException("Invalid setting [{$definition->key()}].");
        }$encoded = json_encode($value, JSON_THROW_ON_ERROR);
        DB::table('scoped_settings')->updateOrInsert(['scope_type' => $scopeType, 'scope_id' => $scopeId, 'key' => $definition->key()], ['value' => $definition->secret() ? Crypt::encryptString($encoded) : $encoded, 'secret' => $definition->secret(), 'updated_at' => now(), 'created_at' => now()]);
    }

    public function resolve(string $key, array $scopes, mixed $fallback = null): mixed
    {
        foreach ($scopes as $type => $id) {
            if ($id === null) {
                continue;
            }$row = DB::table('scoped_settings')->where('scope_type', $type)->where('scope_id', (string) $id)->where('key', $key)->first();
            if ($row) {
                $value = $row->secret ? Crypt::decryptString($row->value) : $row->value;

                return json_decode($value, true, flags: JSON_THROW_ON_ERROR);
            }
        }

        return $fallback;
    }
}
