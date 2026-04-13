<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Models\CRM\AiUsageLog;
use Illuminate\Support\Str;

// BRD: CRM-AI-012 — Centralized writer for AI usage audit records
final class AiUsageLoggingService
{
    /** @param array<string, mixed> $context */
    public function log(
        int $institutionId,
        ?int $campusId,
        ?int $leadId,
        ?int $actorId,
        string $featureKey,
        string $action,
        string $eventName,
        ?string $referenceUuid,
        array $context = [],
    ): AiUsageLog {
        return AiUsageLog::withoutGlobalScopes()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $institutionId,
            'campus_id' => $campusId,
            'lead_id' => $leadId,
            'actor_id' => $actorId,
            'feature_key' => $featureKey,
            'action' => $action,
            'event_name' => $eventName,
            'reference_uuid' => $referenceUuid,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
