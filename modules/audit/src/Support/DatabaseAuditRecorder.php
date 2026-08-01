<?php

namespace Liberu\Foundation\Audit\Support;

use Illuminate\Support\Facades\DB;
use Liberu\Foundation\Audit\Contracts\AuditRecorder;

final class DatabaseAuditRecorder implements AuditRecorder
{
    public function record(string $event, string $subjectType, string|int|null $subjectId, array $before, array $after, AuditContext $context): void
    {
        DB::transaction(function () use ($event, $subjectType, $subjectId, $before, $after, $context): void {
            $previous = DB::table('activity_log')->lockForUpdate()->latest('id')->value('record_hash');
            $changes = json_encode(['before' => $before, 'after' => $after], JSON_THROW_ON_ERROR);
            $properties = json_encode(['tenant_id' => $context->tenantId, 'request_id' => $context->requestId, 'correlation_id' => $context->correlationId, 'reason' => $context->reason], JSON_THROW_ON_ERROR);
            $hash = hash_hmac('sha256', implode('|', [(string) $previous, $event, $subjectType, (string) $subjectId, (string) $context->actorId, $changes, $properties]), (string) config('app.key'));
            DB::table('activity_log')->insert([
                'log_name' => 'audit', 'description' => $event, 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'event' => $event,
                'causer_type' => $context->actorType, 'causer_id' => $context->actorId,
                'attribute_changes' => $changes, 'properties' => $properties, 'previous_hash' => $previous, 'record_hash' => $hash,
                'tenant_ref' => $context->tenantId, 'correlation_id' => $context->correlationId, 'retain_until' => now()->addDays((int) config('audit.retention_days', 2555)),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }
}
