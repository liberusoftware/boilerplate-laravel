<?php

namespace Liberu\Foundation\TwoFactor\Recovery;

use Illuminate\Contracts\Hashing\Hasher;

final readonly class RecoveryCodeHasher
{
    public function __construct(private Hasher $hasher) {}

    /** @param list<string> $codes @return list<string> */
    public function hash(array $codes): array
    {
        return array_map(fn (string $code): string => $this->hasher->make($code), $codes);
    }

    /** @param list<string> $hashes */
    public function verifyAndConsume(string $candidate, array &$hashes): bool
    {
        foreach ($hashes as $index => $hash) {
            if ($this->hasher->check($candidate, $hash)) {
                unset($hashes[$index]);
                $hashes = array_values($hashes);

                return true;
            }
        }

        return false;
    }
}
