<?php

declare(strict_types=1);

namespace App\Observers\CRM\Alumni;

use App\Jobs\CRM\Alumni\AlumniReferralConvertedJob;
use App\Models\CRM\ApplicationConversionLog;

// BRD: CRM-AL-003 — Fire reward accrual when a referred lead converts to enrolled student
class ApplicationConversionReferralObserver
{
    public function created(ApplicationConversionLog $log): void
    {
        $lead = $log->lead;

        if ($lead === null || $lead->referred_by_alumni_id === null) {
            return;
        }

        AlumniReferralConvertedJob::dispatch($lead->id)->onQueue('crm-alumni');
    }
}
