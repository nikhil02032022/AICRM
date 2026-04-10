<?php

declare(strict_types=1);

namespace App\Services\CRM\Counselling;

use App\Enums\CRM\LeadStatus;
use App\Models\CRM\Lead;
use Illuminate\Support\Facades\Log;

// BRD: CRM-EC-012 — Status transitions trigger configured automation actions
final class LeadStatusWorkflowService
{
    /**
     * Run automation triggered by a status transition.
     *
     * Called from TriggerStatusWorkflowListener after LeadStatusChangedEvent fires.
     *
     * BRD: CRM-EC-012 — e.g. COUNSELLING_SCHEDULED → create follow-up task (stub for Group E2),
     *                        LOST → ensure lost_reason is present
     */
    public function handleStatusChange(Lead $lead, LeadStatus $newStatus): void
    {
        // BRD: CRM-CR-002 — No PII in logs
        Log::info('Lead status workflow triggered', [
            'lead_uuid' => $lead->uuid,
            'new_status' => $newStatus->value,
        ]);

        match ($newStatus) {
            LeadStatus::CONTACTED => $this->onContacted($lead),
            LeadStatus::COUNSELLING_SCHEDULED => $this->onCounsellingScheduled($lead),
            LeadStatus::COUNSELLING_DONE => $this->onCounsellingDone($lead),
            LeadStatus::LOST => $this->onLost($lead),
            default => null,
        };
    }

    private function onContacted(Lead $lead): void
    {
        // Lead has been contacted — remove from cold nurture queue (stub for Group F)
        // QueueStopNurtureSequenceJob::dispatch($lead->uuid);
    }

    private function onCounsellingScheduled(Lead $lead): void
    {
        // BRD: CRM-EC-017 — Appointment reminder will be dispatched by CounsellingService
        // Task creation stub for Group E3 (tasks module)
    }

    private function onCounsellingDone(Lead $lead): void
    {
        // BRD: CRM-TF-002 — Auto-create follow-up task (Task module — Group E3/F)
    }

    private function onLost(Lead $lead): void
    {
        // BRD: CRM-MA-007 — Re-engagement sequence can be triggered here (Group F)
        Log::info('Lead marked as lost', [
            'lead_uuid' => $lead->uuid,
            'lost_reason' => $lead->lost_reason?->value,
        ]);
    }
}
