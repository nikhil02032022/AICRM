<?php

declare(strict_types=1);

namespace App\Services\CRM\Integration;

use App\Enums\CRM\AlumniBridgeStatus;
use App\Events\CRM\AlumniBridgeTriggeredEvent;
use App\Jobs\CRM\TriggerAlumniBridgeJob;
use App\Models\CRM\AlumniBridgeLog;
use App\Repositories\CRM\Integration\AlumniBridgeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

// BRD: CRM-EI-008 — Alumni module bridge service — CRM to A2A Alumni handoff on graduation
final class AlumniBridgeService
{
    public function __construct(
        private readonly AlumniBridgeRepositoryInterface $repository
    ) {}

    /**
     * BRD: CRM-EI-008 — List all alumni bridge logs for an institution (paginated)
     */
    public function list(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($institutionId, $perPage);
    }

    /**
     * BRD: CRM-EI-008 — Trigger alumni bridge handoff for a graduated student
     * Dispatches TriggerAlumniBridgeJob for async A2A Alumni API call
     */
    public function trigger(
        int $leadId,
        int $institutionId,
        int $campusId,
        string $erpStudentId,
        array $payloadSummary = []
    ): AlumniBridgeLog {
        $log = $this->repository->create([
            'uuid'            => (string) Str::uuid(),
            'institution_id'  => $institutionId,
            'campus_id'       => $campusId,
            'lead_id'         => $leadId,
            'erp_student_id'  => $erpStudentId,
            'status'          => AlumniBridgeStatus::TRIGGERED,
            'referral_code'   => strtoupper(Str::random(8)),
            'payload_summary' => $payloadSummary,
        ]);

        // BRD: CRM-EI-008 — Async ERP Alumni API call — never synchronous
        TriggerAlumniBridgeJob::dispatch($log->id)->onQueue('crm-integrations');

        AlumniBridgeTriggeredEvent::dispatch($log);

        return $log;
    }

    /**
     * BRD: CRM-EI-008 — Mark bridge as successfully created, store alumni ID
     */
    public function markSuccess(AlumniBridgeLog $log, string $erpAlumniId): AlumniBridgeLog
    {
        return $this->repository->update($log, [
            'status'        => AlumniBridgeStatus::SUCCESS,
            'erp_alumni_id' => $erpAlumniId,
            'bridged_at'    => now(),
            'error_message' => null,
        ]);
    }

    /**
     * BRD: CRM-EI-008 — Mark bridge as failed
     */
    public function markFailed(AlumniBridgeLog $log, string $error): AlumniBridgeLog
    {
        return $this->repository->update($log, [
            'status'        => AlumniBridgeStatus::FAILED,
            'error_message' => $error,
        ]);
    }

    /**
     * BRD: CRM-EI-008 — Increment referral count for tracking referral impact
     */
    public function incrementReferrals(AlumniBridgeLog $log): void
    {
        $log->increment('referrals_count');
    }

    /**
     * BRD: CRM-EI-008 — Find by UUID
     */
    public function findByUuid(string $uuid): ?AlumniBridgeLog
    {
        return $this->repository->findByUuid($uuid);
    }
}
