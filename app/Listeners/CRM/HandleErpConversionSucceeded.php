<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\ErpConversionSucceededEvent;
use App\Jobs\CRM\TriggerErpOnboardingWorkflowsJob;
use App\Models\CRM\ApplicationConversionLog;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-AP-018 — On conversion success, dispatch onboarding workflow job
final class HandleErpConversionSucceeded implements ShouldQueue
{
    public function handle(ErpConversionSucceededEvent $event): void
    {
        $log = ApplicationConversionLog::withoutGlobalScopes()
            ->where('application_uuid', $event->application->uuid)
            ->where('status', 'success')
            ->latest('completed_at')
            ->first();

        if ($log === null) {
            return;
        }

        TriggerErpOnboardingWorkflowsJob::dispatch(
            $log->uuid,
            $event->application->institution_id,
            $event->erpStudentId,
        );
    }
}
