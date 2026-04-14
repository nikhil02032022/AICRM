<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Integration;

use App\Models\CRM\LmsEnrolmentLog;
use Illuminate\Pagination\LengthAwarePaginator;

// BRD: CRM-EI-010 — Eloquent implementation of LMS enrolment log repository
final class EloquentLmsEnrolmentRepository implements LmsEnrolmentRepositoryInterface
{
    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return LmsEnrolmentLog::where('institution_id', $institutionId)
            ->with('lead')
            ->latest()
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?LmsEnrolmentLog
    {
        return LmsEnrolmentLog::where('uuid', $uuid)->with('lead')->first();
    }

    public function findByErpStudentId(string $erpStudentId, int $institutionId): ?LmsEnrolmentLog
    {
        return LmsEnrolmentLog::where('institution_id', $institutionId)
            ->where('erp_student_id', $erpStudentId)
            ->latest()
            ->first();
    }

    public function create(array $data): LmsEnrolmentLog
    {
        return LmsEnrolmentLog::create($data);
    }

    public function update(LmsEnrolmentLog $log, array $data): LmsEnrolmentLog
    {
        $log->update($data);

        return $log->refresh();
    }
}
