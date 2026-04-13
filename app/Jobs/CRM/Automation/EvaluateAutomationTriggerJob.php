<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Automation;

use App\Models\CRM\Lead;
use App\Services\CRM\Marketing\AutomationTriggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-MA-002 — Queue job to evaluate event-driven trigger types for a lead
final class EvaluateAutomationTriggerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly int $institutionId,
        public readonly int $leadId,
        public readonly string $triggerType,
        public readonly array $context = [],
    ) {
        $this->onQueue('crm-automation');
    }

    public function handle(AutomationTriggerService $triggerService): void
    {
        $lead = Lead::withoutGlobalScopes()
            ->where('institution_id', $this->institutionId)
            ->whereKey($this->leadId)
            ->first();

        if ($lead === null) {
            return;
        }

        $triggerService->evaluateForLead($lead, $this->triggerType, $this->context);
    }
}
