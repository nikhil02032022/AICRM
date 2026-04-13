<?php

declare(strict_types=1);

namespace App\Services\CRM\Marketing;

use App\Jobs\CRM\Automation\ExecuteWorkflowActionsJob;
use App\Models\CRM\AutomationWorkflow;
use App\Models\CRM\Lead;
use App\Models\CRM\WorkflowInstance;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

// BRD: CRM-MA-002 — Trigger evaluation engine for workflow enrollment
final class AutomationTriggerService
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function evaluateForLead(Lead $lead, string $triggerType, array $context = []): int
    {
        $workflows = AutomationWorkflow::withoutGlobalScopes()
            ->where('institution_id', $lead->institution_id)
            ->where('status', 'active')
            ->where('trigger_type', $triggerType)
            ->get();

        $created = 0;

        foreach ($workflows as $workflow) {
            if (! $this->eligibleForCampus($workflow, $lead)) {
                continue;
            }

            if (! $this->matchesConfig($workflow, $lead, $context)) {
                continue;
            }

            if ($this->alreadyEnrolledToday($workflow, $lead->id, $triggerType)) {
                continue;
            }

            $instance = $this->createInstance($workflow, $lead, $triggerType, $context);
            ExecuteWorkflowActionsJob::dispatch((int) $workflow->institution_id, (int) $instance->id);
            $created++;
        }

        return $created;
    }

    public function evaluateTimedTriggers(CarbonImmutable $now): int
    {
        $workflows = AutomationWorkflow::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('trigger_type', 'date_time_based')
            ->get();

        $created = 0;

        foreach ($workflows as $workflow) {
            $runAtRaw = $workflow->trigger_config['run_at'] ?? null;

            if (! is_string($runAtRaw)) {
                continue;
            }

            $runAt = CarbonImmutable::parse($runAtRaw);

            if ($runAt->isFuture()) {
                continue;
            }

            $leads = $this->eligibleLeadsForWorkflow($workflow);

            foreach ($leads as $lead) {
                if ($this->alreadyEnrolledToday($workflow, (int) $lead->id, 'date_time_based')) {
                    continue;
                }

                $instance = $this->createInstance($workflow, $lead, 'date_time_based', [
                    'run_at' => $runAt->toIso8601String(),
                    'evaluated_at' => $now->toIso8601String(),
                ]);

                ExecuteWorkflowActionsJob::dispatch((int) $workflow->institution_id, (int) $instance->id);

                $created++;
            }
        }

        return $created;
    }

    // BRD: CRM-MA-009 — Evaluate event-based journeys (open day, webinar, result, deadline reminders).
    public function evaluateEventBasedTriggers(CarbonImmutable $now): int
    {
        $workflows = AutomationWorkflow::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('trigger_type', 'event_based')
            ->get();

        $created = 0;

        foreach ($workflows as $workflow) {
            $config = is_array($workflow->trigger_config) ? $workflow->trigger_config : [];
            $eventAtRaw = $config['event_at'] ?? null;

            if (! is_string($eventAtRaw) || trim($eventAtRaw) === '') {
                continue;
            }

            $eventAt = CarbonImmutable::parse($eventAtRaw);
            $eventType = strtolower(trim((string) ($config['event_type'] ?? 'generic_event')));
            $windowMinutes = max(1, (int) ($config['window_minutes'] ?? 60));

            $dueMoments = $this->resolveDueEventMoments($eventAt, $config, $now, $windowMinutes);

            if ($dueMoments === []) {
                continue;
            }

            $leads = $this->eligibleLeadsForWorkflow($workflow);

            foreach ($dueMoments as $moment) {
                $triggerContextType = sprintf('event_based:%s:%s', $eventType, $moment['tag']);

                foreach ($leads as $lead) {
                    if ($this->alreadyEnrolledToday($workflow, (int) $lead->id, $triggerContextType)) {
                        continue;
                    }

                    $instance = $this->createInstance($workflow, $lead, $triggerContextType, [
                        'event_type' => $eventType,
                        'event_at' => $eventAt->toIso8601String(),
                        'scheduled_for' => $moment['scheduled_for']->toIso8601String(),
                        'window_minutes' => $windowMinutes,
                        'reminder_days' => $moment['reminder_days'],
                        'evaluated_at' => $now->toIso8601String(),
                    ]);

                    ExecuteWorkflowActionsJob::dispatch((int) $workflow->institution_id, (int) $instance->id);
                    $created++;
                }
            }
        }

        return $created;
    }

    public function evaluateInactivityTriggers(CarbonImmutable $now): int
    {
        $workflows = AutomationWorkflow::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('trigger_type', 'inactivity_timeout')
            ->get();

        $created = 0;

        foreach ($workflows as $workflow) {
            $days = (int) ($workflow->trigger_config['inactivity_days'] ?? 30);
            $cutoff = $now->subDays(max(1, $days));

            $leads = $this->eligibleLeadsForWorkflow($workflow)
                ->filter(function (Lead $lead) use ($cutoff): bool {
                    return ! $lead->activities()
                        ->where('created_at', '>=', $cutoff)
                        ->exists();
                });

            foreach ($leads as $lead) {
                if ($this->alreadyEnrolledToday($workflow, (int) $lead->id, 'inactivity_timeout')) {
                    continue;
                }

                $instance = $this->createInstance($workflow, $lead, 'inactivity_timeout', [
                    'inactivity_days' => $days,
                    'evaluated_at' => $now->toIso8601String(),
                ]);

                ExecuteWorkflowActionsJob::dispatch((int) $workflow->institution_id, (int) $instance->id);

                $created++;
            }
        }

        // BRD: CRM-MA-007 — Support dedicated re-engagement journeys for inactive leads.
        $reEngagementWorkflows = AutomationWorkflow::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('trigger_type', 're_engagement')
            ->get();

        foreach ($reEngagementWorkflows as $workflow) {
            $reason = strtolower((string) ($workflow->trigger_config['reason'] ?? ''));
            if (! in_array($reason, ['inactive', 'inactivity'], true)) {
                continue;
            }

            $days = (int) ($workflow->trigger_config['inactivity_days'] ?? 30);
            $cutoff = $now->subDays(max(1, $days));

            $leads = $this->eligibleLeadsForWorkflow($workflow)
                ->filter(function (Lead $lead) use ($cutoff): bool {
                    return ! $lead->activities()
                        ->where('created_at', '>=', $cutoff)
                        ->exists();
                });

            foreach ($leads as $lead) {
                if ($this->alreadyEnrolledToday($workflow, (int) $lead->id, 're_engagement:inactive')) {
                    continue;
                }

                $instance = $this->createInstance($workflow, $lead, 're_engagement:inactive', [
                    'reason' => 'inactive',
                    'inactivity_days' => $days,
                    'evaluated_at' => $now->toIso8601String(),
                ]);

                ExecuteWorkflowActionsJob::dispatch((int) $workflow->institution_id, (int) $instance->id);

                $created++;
            }
        }

        return $created;
    }

    private function eligibleForCampus(AutomationWorkflow $workflow, Lead $lead): bool
    {
        return $workflow->campus_id === null || (int) $workflow->campus_id === (int) $lead->campus_id;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array{tag: string, scheduled_for: CarbonImmutable, reminder_days: int|null}>
     */
    private function resolveDueEventMoments(
        CarbonImmutable $eventAt,
        array $config,
        CarbonImmutable $now,
        int $windowMinutes,
    ): array {
        $due = [];
        $windowStart = $now->subMinutes($windowMinutes);

        if ($eventAt->lessThanOrEqualTo($now) && $eventAt->greaterThanOrEqualTo($windowStart)) {
            $due[] = [
                'tag' => 'event',
                'scheduled_for' => $eventAt,
                'reminder_days' => null,
            ];
        }

        $reminderOffsets = array_values(array_filter(
            is_array($config['reminder_offsets_days'] ?? null) ? $config['reminder_offsets_days'] : [],
            static fn (mixed $value): bool => is_numeric($value) && (int) $value >= 0,
        ));

        foreach ($reminderOffsets as $offset) {
            $days = (int) $offset;
            $scheduledFor = $eventAt->subDays($days);

            if (! $scheduledFor->lessThanOrEqualTo($now) || ! $scheduledFor->greaterThanOrEqualTo($windowStart)) {
                continue;
            }

            $due[] = [
                'tag' => sprintf('reminder_%dd', $days),
                'scheduled_for' => $scheduledFor,
                'reminder_days' => $days,
            ];
        }

        return $due;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function matchesConfig(AutomationWorkflow $workflow, Lead $lead, array $context): bool
    {
        $config = is_array($workflow->trigger_config) ? $workflow->trigger_config : [];

        $source = $config['source'] ?? null;
        if (is_string($source) && $source !== '' && $lead->source?->value !== $source) {
            return false;
        }

        $requiredStatus = $config['new_status'] ?? null;
        if (is_string($requiredStatus) && $requiredStatus !== '') {
            $actualStatus = (string) ($context['new_status'] ?? $lead->status?->value);

            if ($requiredStatus !== $actualStatus) {
                return false;
            }
        }

        $requiredTemperature = $config['new_temperature'] ?? null;
        if (is_string($requiredTemperature) && $requiredTemperature !== '') {
            $actualTemperature = (string) ($context['new_temperature'] ?? $lead->temperature?->value);

            if ($requiredTemperature !== $actualTemperature) {
                return false;
            }
        }

        // BRD: CRM-MA-008 — Programme-specific journeys configurable by programme IDs/codes.
        $programmeIds = array_values(array_filter(
            is_array($config['programme_ids'] ?? null) ? $config['programme_ids'] : [],
            static fn (mixed $value): bool => is_numeric($value),
        ));

        $programmeCodes = array_values(array_filter(
            is_array($config['programme_codes'] ?? null) ? $config['programme_codes'] : [],
            static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
        ));

        if ($programmeIds !== [] || $programmeCodes !== []) {
            $leadProgrammes = $lead->programmeInterests()->get(['crm_programmes.id', 'crm_programmes.code']);

            if ($programmeIds !== []) {
                $leadProgrammeIds = $leadProgrammes
                    ->pluck('id')
                    ->map(static fn (mixed $value): int => (int) $value)
                    ->all();

                $requiredIds = array_map(static fn (mixed $value): int => (int) $value, $programmeIds);

                if (array_intersect($leadProgrammeIds, $requiredIds) === []) {
                    return false;
                }
            }

            if ($programmeCodes !== []) {
                $leadProgrammeCodes = $leadProgrammes
                    ->pluck('code')
                    ->filter(static fn (mixed $code): bool => is_string($code) && trim($code) !== '')
                    ->map(static fn (string $code): string => strtolower(trim($code)))
                    ->all();

                $requiredCodes = array_map(
                    static fn (string $code): string => strtolower(trim($code)),
                    $programmeCodes,
                );

                if (array_intersect($leadProgrammeCodes, $requiredCodes) === []) {
                    return false;
                }
            }
        }

        // BRD: CRM-MA-007 — Re-engagement workflow matching by reason (cold|inactive).
        $requiredReason = $config['reason'] ?? null;
        if (is_string($requiredReason) && $requiredReason !== '') {
            $actualReason = strtolower((string) ($context['reason'] ?? ''));

            if (strtolower($requiredReason) !== $actualReason) {
                return false;
            }
        }

        return true;
    }

    private function alreadyEnrolledToday(AutomationWorkflow $workflow, int $leadId, string $triggerType): bool
    {
        return WorkflowInstance::withoutGlobalScopes()
            ->where('institution_id', $workflow->institution_id)
            ->where('automation_workflow_id', $workflow->id)
            ->where('lead_id', $leadId)
            ->whereDate('created_at', now()->toDateString())
            ->where('context->trigger_type', $triggerType)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function createInstance(AutomationWorkflow $workflow, Lead $lead, string $triggerType, array $context): WorkflowInstance
    {
        $firstStep = $workflow->steps()->orderBy('step_order')->first();

        return WorkflowInstance::create([
            'institution_id' => $workflow->institution_id,
            'campus_id' => $workflow->campus_id ?? $lead->campus_id,
            'automation_workflow_id' => $workflow->id,
            'lead_id' => $lead->id,
            'status' => 'pending',
            'current_workflow_step_id' => $firstStep?->id,
            'started_at' => now(),
            'context' => [
                'trigger_type' => $triggerType,
                'trigger_payload' => $context,
            ],
        ]);
    }

    /** @return Collection<int, Lead> */
    private function eligibleLeadsForWorkflow(AutomationWorkflow $workflow): Collection
    {
        return Lead::withoutGlobalScopes()
            ->where('institution_id', $workflow->institution_id)
            ->when($workflow->campus_id !== null, fn ($query) => $query->where('campus_id', $workflow->campus_id))
            ->whereNull('deleted_at')
            ->get();
    }
}
