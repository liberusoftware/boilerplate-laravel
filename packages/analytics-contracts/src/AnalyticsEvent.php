<?php

namespace Liberu\Analytics\Contracts;

use DateTimeImmutable;

final readonly class AnalyticsEvent
{
    /** @param array<string, mixed> $properties */
    public function __construct(
        public string $id,
        public string $name,
        public string $version,
        public DateTimeImmutable $occurredAt,
        public string $source,
        public ?string $actorRef,
        public ?string $sessionRef,
        public ?string $tenantRef,
        public ?string $locale,
        public ?string $currency,
        public string $consentCategory,
        public array $properties = [],
    ) {}
}
