<?php

namespace Liberu\Foundation\Settings\Contracts;

interface SettingDefinition
{
    public function key(): string;

    public function validate(mixed $value): bool;

    public function secret(): bool;
}
