<?php

declare(strict_types=1);

namespace App\Observers\CRM\Alumni;

use App\Enums\CRM\ApplicationStatus;
use App\Jobs\CRM\Alumni\AlumniPipelineJob;
use App\Models\CRM\Application;
use App\Models\CRM\Alumni\AlumniPipeline;
use App\Services\CRM\Alumni\AlumniPipelineService;

// BRD: CRM-AL-001 — Auto-populate alumni pipeline from enrolled students
class GraduationObserver
{
    public function updated(Application $application): void
    {
        if (
            $application->wasChanged('status')
            && $application->status === ApplicationStatus::ENROLLED
        ) {
            $existing = AlumniPipeline::withoutGlobalScopes()
                ->where('lead_id', $application->lead_id)
                ->where('application_id', $application->id)
                ->exists();

            if (! $existing) {
                app(AlumniPipelineService::class)->enqueue($application);
            }
        }
    }
}
