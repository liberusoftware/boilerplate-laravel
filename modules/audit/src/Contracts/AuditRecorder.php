<?php

namespace Liberu\Foundation\Audit\Contracts;

use Liberu\Foundation\Audit\Support\AuditContext;

interface AuditRecorder
{
    public function record(string $event, string $subjectType, string|int|null $subjectId, array $before, array $after, AuditContext $context): void;
}
