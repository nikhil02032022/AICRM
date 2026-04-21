<?php

declare(strict_types=1);

namespace App\Listeners\CRM\Payments;

use App\Events\CRM\ErpConversionSucceededEvent;
use App\Jobs\CRM\Payments\MigrateConvertedApplicationFeesJob;
use App\Models\CRM\ApplicationConversionLog;
use Illuminate\Contracts\Queue\ShouldQueue;

// BRD: CRM-FM-013 — On ERP conversion success, push fee ledger.
final class MigrateFeesOnApplicationConverted implements ShouldQueue
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

        MigrateConvertedApplicationFeesJob::dispatch(
            $log->uuid,
            $event->application->institution_id,
            $event->erpStudentId,
        );
    }
}
