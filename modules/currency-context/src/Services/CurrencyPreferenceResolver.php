<?php

namespace Liberu\Foundation\Currency\Services;

use Illuminate\Support\Facades\DB;

final class CurrencyPreferenceResolver
{
    public function resolve(array $scopes, string $fallback): string
    {
        foreach ($scopes as $type => $id) {
            if ($id === null) {
                continue;
            }$currency = DB::table('currency_preferences')->where('scope_type', $type)->where('scope_id', (string) $id)->value('currency');
            if (is_string($currency)) {
                return strtoupper($currency);
            }
        }

        return strtoupper($fallback);
    }
}
