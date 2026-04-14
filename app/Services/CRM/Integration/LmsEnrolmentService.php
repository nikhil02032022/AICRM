<?php

declare(strict_types=1);

namespace App\Services\CRM\Integration;

use App\Enums\CRM\LmsEnrolmentStatus;
use App\Events\CRM\LmsEnrolmentTriggeredEvent;
use App\Jobs\CRM\TriggerLmsEnrolmentJob;
use App\Models\CRM\LmsEnrolmentLog;
use App\Repositories\CRM\Integration\LmsEnrolmentRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

// BRD: CRM-EI-010 — LMS enrolment trigger service — auto-enrol students in CamPLUS/Moodle
final class LmsEnrolmentService
{
    public function __construct(
        private readonly LmsEnrolmentRepositoryInterface $repository
    ) {}

    /**
     * BRD: CRM-EI-010 — List all LMS enrolment logs for an institution (paginated)
     */
    public function list(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($institutionId, $perPage);
    }

    /**
     * BRD: CRM-EI-010 — Trigger LMS enrolment for an admitted student
     * Dispatches TriggerLmsEnrolmentJob for async LMS API call
     */
    public function trigger(
        int $leadId,
        int $institutionId,
        int $campusId,
        string $erpStudentId,
        string $lmsProvider,
        string $lmsCourseId
    ): LmsEnrolmentLog {
        $log = $this->repository->create([
            'uuid'           => (string) Str::uuid(),
            'institution_id' => $institutionId,
            'campus_id'      => $campusId,
            'lead_id'        => $leadId,
            'erp_student_id' => $erpStudentId,
            'lms_provider'   => $lmsProvider,
            'lms_course_id'  => $lmsCourseId,
            'status'         => LmsEnrolmentStatus::QUEUED,
            'attempt_count'  => 0,
        ]);

        // BRD: CRM-EI-010 — Async LMS API call — never synchronous
        TriggerLmsEnrolmentJob::dispatch($log->id)->onQueue('crm-integrations');

        LmsEnrolmentTriggeredEvent::dispatch($log);

        return $log;
    }

    /**
     * BRD: CRM-EI-010 — Mark enrolment as successful, store LMS user ID
     */
    public function markEnrolled(LmsEnrolmentLog $log, string $lmsUserId): LmsEnrolmentLog
    {
        return $this->repository->update($log, [
            'status'      => LmsEnrolmentStatus::ENROLLED,
            'lms_user_id' => $lmsUserId,
            'enrolled_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * BRD: CRM-EI-010 — Mark enrolment as failed (will retry up to 3 times via job)
     */
    public function markFailed(LmsEnrolmentLog $log, string $error): LmsEnrolmentLog
    {
        return $this->repository->update($log, [
            'status'        => LmsEnrolmentStatus::FAILED,
            'error_message' => $error,
        ]);
    }

    /**
     * BRD: CRM-EI-010 — Increment attempt counter for retry tracking
     */
    public function incrementAttempts(LmsEnrolmentLog $log): void
    {
        $log->increment('attempt_count');
    }

    /**
     * BRD: CRM-EI-010 — Find by UUID
     */
    public function findByUuid(string $uuid): ?LmsEnrolmentLog
    {
        return $this->repository->findByUuid($uuid);
    }
}
