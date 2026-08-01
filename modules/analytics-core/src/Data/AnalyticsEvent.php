<?php

namespace Liberu\Foundation\Analytics\Data;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class AnalyticsEvent
{
    /** @param array<string,scalar|array|null> $properties */
    public function __construct(public string $id, public string $name, public string $version, public DateTimeImmutable $occurredAt, public string $source, public ?string $actorRef, public ?string $sessionRef, public ?string $tenantRef, public ?string $locale, public ?string $currency, public string $consentCategory, public array $properties = [])
    {
        if (! preg_match('/^[a-z][a-z0-9_.-]+$/', $name)) {
            throw new InvalidArgumentException('Invalid analytics event name.');
        }
    }
}
