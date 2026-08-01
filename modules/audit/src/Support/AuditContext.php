<?php

namespace Liberu\Foundation\Audit\Support;

final readonly class AuditContext
{
    public function __construct(public string|int|null $actorId, public ?string $actorType, public ?string $tenantId, public ?string $requestId, public ?string $correlationId, public ?string $reason = null) {}
}
