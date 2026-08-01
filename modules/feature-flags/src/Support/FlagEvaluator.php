<?php

namespace Liberu\Foundation\FeatureFlags\Support;

final class FlagEvaluator
{
    /** @param array{enabled?:bool,percentage?:int,environments?:list<string>,tenants?:list<string|int>,actors?:list<string|int>} $rule */
    public function enabled(string $key, array $rule, string $environment, string|int|null $tenant = null, string|int|null $actor = null): bool
    {
        if (! ($rule['enabled'] ?? false)) {
            return false;
        }
        if (isset($rule['expires_at']) && new \DateTimeImmutable((string) $rule['expires_at']) <= new \DateTimeImmutable()) {
            return false;
        }
        if (isset($rule['environments']) && ! in_array($environment, $rule['environments'], true)) {
            return false;
        }
        if ($tenant !== null && isset($rule['tenants']) && in_array($tenant, $rule['tenants'], true)) {
            return true;
        }
        if ($actor !== null && isset($rule['actors']) && in_array($actor, $rule['actors'], true)) {
            return true;
        }
        $percentage = max(0, min(100, (int) ($rule['percentage'] ?? 100)));
        $subject = (string) ($actor ?? $tenant ?? 'anonymous');

        return (hexdec(substr(hash('sha256', $key.'|'.$subject), 0, 8)) % 100) < $percentage;
    }
}
