<?php

declare(strict_types=1);

namespace App\Jobs\CRM\Automation;

use App\Services\CRM\Marketing\AutomationTriggerService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-MA-002 — Queue job for date/time-based trigger evaluation
final class EvaluateTimedAutomationTriggersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct()
    {
        $this->onQueue('crm-automation');
    }

    public function handle(AutomationTriggerService $triggerService): void
    {
        $triggerService->evaluateTimedTriggers(CarbonImmutable::now());
    }
}
