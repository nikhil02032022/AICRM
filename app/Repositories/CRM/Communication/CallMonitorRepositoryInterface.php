<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Communication;

use App\Models\CRM\CallLog;
use App\Models\CRM\CallMonitorLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CallMonitorRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function createSession(CallLog $callLog, int $supervisorId, array $payload): CallMonitorLog;

    public function stopSession(CallMonitorLog $monitorLog, ?string $notes = null): CallMonitorLog;

    public function findActiveSession(int $callLogId, int $supervisorId): ?CallMonitorLog;

    public function activeCalls(int $limit = 30): LengthAwarePaginator;
}
