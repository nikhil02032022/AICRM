<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Enums\CRM\ActivityType;
use App\Events\CRM\ErpStudentMatchedEvent;
use App\Repositories\CRM\Activity\ActivityRepositoryInterface;

// BRD: CRM-LC-020 — Log ERP match event to lead activity timeline
// DPDP: No PII (student UUID, enrollment no) in body — only admitted course label
final class LogErpMatchActivity
{
    public function __construct(
        private readonly ActivityRepositoryInterface $activityRepository,
    ) {}

    public function handle(ErpStudentMatchedEvent $event): void
    {
        $lead = $event->lead;
        $erpStudent = $event->erpStudent;

        $alumniLabel = $erpStudent->isAlumni ? 'alumni' : 'student';
        $body = "ERP Student Master match found — {$alumniLabel} admitted to '{$erpStudent->admittedCourse}'. Record linked automatically.";

        $this->activityRepository->createSystemEntry(
            subjectType: \App\Models\CRM\Lead::class,
            subjectId: $lead->id,
            institutionId: $lead->institution_id,
            type: ActivityType::SYSTEM,
            body: $body,
            metadata: [
                'erp_match_status' => 'matched',
                'is_alumni' => $erpStudent->isAlumni,
                // enrollment_no is institutional ID, not PII per DPDP
                'enrollment_no' => $erpStudent->enrollmentNo,
            ],
        );
    }
}
