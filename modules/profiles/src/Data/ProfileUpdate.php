<?php

namespace Liberu\Foundation\Profiles\Data;

final readonly class ProfileUpdate
{
    public function __construct(public string $name, public ?string $locale = null, public ?string $timezone = null, public ?string $theme = null) {}
}
