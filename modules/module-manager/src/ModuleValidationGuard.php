<?php

namespace Liberu\Foundation\ModuleManager;

final readonly class ModuleValidationGuard
{
    public function __construct(private ModuleValidator $validator) {}

    public function ensureValid(ModuleRegistry $registry, string $laravelVersion): void
    {
        $errors = $this->validator->validate($registry, $laravelVersion);
        if ($errors !== []) {
            throw new \RuntimeException("Module validation failed:\n- ".implode("\n- ", $errors));
        }
    }
}
