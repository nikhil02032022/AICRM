<?php

declare(strict_types=1);

namespace App\Listeners\CRM\Scholarships;

use App\Events\CRM\Scholarships\ScholarshipAwardApproved;
use App\Models\CRM\Application;
use App\Services\CRM\Payments\ApplicationInstallmentService;

// BRD: CRM-FM-008 + CRM-FM-009 — Apply an approved waiver against the application's open installments.
class ApplyWaiverOnApproved
{
    public function __construct(private ApplicationInstallmentService $installments)
    {
    }

    public function handle(ScholarshipAwardApproved $event): void
    {
        $award = $event->award;
        $application = Application::withoutGlobalScopes()
            ->where('uuid', $award->application_uuid)
            ->first();

        if (! $application) {
            return;
        }

        $this->installments->recompute($application, (float) $award->amount);
    }
}
