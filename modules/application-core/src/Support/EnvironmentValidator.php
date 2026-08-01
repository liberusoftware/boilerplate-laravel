<?php

namespace Liberu\Foundation\ApplicationCore\Support;

use RuntimeException;

final class EnvironmentValidator
{
    public function validate(): void
    {
        foreach ((array) config('application-core.required_configuration', []) as $key) {
            if (config($key) === null) {
                throw new RuntimeException("Required configuration [{$key}] is missing.");
            }
        }if (app()->environment('production') && config('app.debug')) {
            throw new RuntimeException('APP_DEBUG must be false in production.');
        }
    }
}
