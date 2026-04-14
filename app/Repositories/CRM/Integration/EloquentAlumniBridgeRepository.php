<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Integration;

use App\Models\CRM\AlumniBridgeLog;
use Illuminate\Pagination\LengthAwarePaginator;

// BRD: CRM-EI-008 — Eloquent implementation of alumni bridge log repository
final class EloquentAlumniBridgeRepository implements AlumniBridgeRepositoryInterface
{
    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return AlumniBridgeLog::where('institution_id', $institutionId)
            ->with('lead')
            ->latest()
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?AlumniBridgeLog
    {
        return AlumniBridgeLog::where('uuid', $uuid)->with('lead')->first();
    }

    public function findByErpStudentId(string $erpStudentId, int $institutionId): ?AlumniBridgeLog
    {
        return AlumniBridgeLog::where('institution_id', $institutionId)
            ->where('erp_student_id', $erpStudentId)
            ->latest()
            ->first();
    }

    public function create(array $data): AlumniBridgeLog
    {
        return AlumniBridgeLog::create($data);
    }

    public function update(AlumniBridgeLog $log, array $data): AlumniBridgeLog
    {
        $log->update($data);

        return $log->refresh();
    }
}
