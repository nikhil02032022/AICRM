<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Integration;

use App\Models\CRM\LmsEnrolmentLog;
use Illuminate\Pagination\LengthAwarePaginator;

// BRD: CRM-EI-010 — LMS enrolment log repository interface
interface LmsEnrolmentRepositoryInterface
{
    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?LmsEnrolmentLog;

    public function findByErpStudentId(string $erpStudentId, int $institutionId): ?LmsEnrolmentLog;

    public function create(array $data): LmsEnrolmentLog;

    public function update(LmsEnrolmentLog $log, array $data): LmsEnrolmentLog;
}
