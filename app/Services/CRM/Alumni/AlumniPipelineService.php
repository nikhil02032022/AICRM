<?php

declare(strict_types=1);

namespace App\Services\CRM\Alumni;

use App\Enums\CRM\Alumni\AlumniPipelineStatus;
use App\Jobs\CRM\Alumni\AlumniPipelineJob;
use App\Models\CRM\Application;
use App\Models\CRM\Alumni\AlumniPipeline;

// BRD: CRM-AL-001 — Auto-populate alumni pipeline from enrolled students
class AlumniPipelineService
{
    public function enqueue(Application $application): AlumniPipeline
    {
        $record = AlumniPipeline::withoutGlobalScopes()->firstOrCreate(
            [
                'lead_id'        => $application->lead_id,
                'application_id' => $application->id,
            ],
            [
                'institution_id' => $application->institution_id,
                'programme_id'   => $application->programme_id,
                'alumni_status'  => AlumniPipelineStatus::Pending->value,
            ]
        );

        AlumniPipelineJob::dispatch($record)->onQueue('crm-alumni');

        return $record;
    }

    public function markEligible(AlumniPipeline $record): void
    {
        $record->update(['alumni_status' => AlumniPipelineStatus::Eligible->value]);
    }

    public function markSynced(AlumniPipeline $record): void
    {
        $record->update(['alumni_status' => AlumniPipelineStatus::Synced->value]);
    }

    public function markFailed(AlumniPipeline $record): void
    {
        $record->update(['alumni_status' => AlumniPipelineStatus::Failed->value]);
    }
}
