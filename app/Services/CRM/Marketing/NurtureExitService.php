<?php

declare(strict_types=1);

namespace App\Services\CRM\Marketing;

use App\Enums\CRM\LeadStatus;
use App\Models\CRM\AutomationWorkflow;
use App\Models\CRM\Lead;
use App\Models\CRM\WorkflowInstance;
use Carbon\CarbonImmutable;

// BRD: CRM-MA-006 — Auto-remove leads from nurture sequences when status reaches Contacted or higher
final class NurtureExitService
{
    public function exitForLeadOnStatus(Lead $lead, LeadStatus $newStatus): int
    {
        if (! $this->isContactedOrHigher($newStatus)) {
            return 0;
        }

        $exited = 0;
        $exitedAt = CarbonImmutable::now();

        $instances = WorkflowInstance::withoutGlobalScopes()
            ->where('institution_id', $lead->institution_id)
            ->where('lead_id', $lead->id)
            ->whereIn('status', ['pending', 'running'])
            ->with('workflow')
            ->get();

        foreach ($instances as $instance) {
            if (! $this->isNurtureWorkflow($instance->workflow)) {
                continue;
            }

            $context = is_array($instance->context) ? $instance->context : [];
            $context['exit_reason'] = 'lead_status_progressed';
            $context['exited_on_status'] = $newStatus->value;
            $context['exited_at'] = $exitedAt->toIso8601String();

            $instance->update([
                'status' => 'exited',
                'current_workflow_step_id' => null,
                'completed_at' => $exitedAt,
                'context' => $context,
            ]);

            $exited++;
        }

        return $exited;
    }

    private function isContactedOrHigher(LeadStatus $status): bool
    {
        return in_array($status, [
            LeadStatus::CONTACTED,
            LeadStatus::COUNSELLING_SCHEDULED,
            LeadStatus::COUNSELLING_DONE,
            LeadStatus::APPLICATION_STARTED,
            LeadStatus::APPLICATION_SUBMITTED,
            LeadStatus::OFFER_ISSUED,
            LeadStatus::FEE_PAID,
            LeadStatus::ENROLLED,
            LeadStatus::DEFERRED,
        ], true);
    }

    private function isNurtureWorkflow(?AutomationWorkflow $workflow): bool
    {
        if ($workflow === null) {
            return false;
        }

        $config = is_array($workflow->trigger_config) ? $workflow->trigger_config : [];
        $journeyType = strtolower(trim((string) ($config['journey_type'] ?? $config['sequence_type'] ?? $config['category'] ?? '')));
        $isNurtureFlag = (bool) ($config['is_nurture'] ?? false);

        if ($isNurtureFlag || $journeyType === 'nurture') {
            return true;
        }

        if ($workflow->trigger_type === 'inactivity_timeout') {
            return true;
        }

        $name = strtolower($workflow->name);

        return str_contains($name, 'nurture') || str_contains($name, 'drip');
    }
}
