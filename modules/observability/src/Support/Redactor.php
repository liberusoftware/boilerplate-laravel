<?php

namespace Liberu\Foundation\Observability\Support;

final class Redactor
{
    public function redact(array $context): array
    {
        $sensitive = array_map('strtolower', (array) config('observability.sensitive_keys', ['password', 'token', 'secret', 'authorization', 'cookie']));
        array_walk_recursive($context, function (&$value, $key) use ($sensitive): void {
            foreach ($sensitive as $needle) {
                if (str_contains(strtolower((string) $key), $needle)) {
                    $value = '[REDACTED]';
                    break;
                }
            }
        });

        return $context;
    }
}
