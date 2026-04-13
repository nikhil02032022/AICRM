<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\Models\CRM\CallLog;
use App\Models\CRM\CallMonitorLog;
use App\Repositories\CRM\Communication\CallMonitorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

// BRD: CRM-TC-005 — Supervisor call monitoring service (listen/whisper/barge-in)
final class CallMonitorService
{
    public function __construct(
        private readonly CallMonitorRepositoryInterface $repository,
    ) {}

    /** @param array<string, mixed> $filters */
    public function paginateSessions(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function activeCalls(int $perPage = 30): LengthAwarePaginator
    {
        return $this->repository->activeCalls($perPage);
    }

    /** @param array<string, mixed> $payload */
    public function startSession(CallLog $callLog, int $supervisorId, array $payload): CallMonitorLog
    {
        if ($callLog->status->isTerminal()) {
            throw new InvalidArgumentException('Monitoring cannot start on completed/failed calls.');
        }

        if (! $callLog->call_consent_given) {
            throw new InvalidArgumentException('Monitoring is blocked because call consent is not available.');
        }

        $existing = $this->repository->findActiveSession($callLog->id, $supervisorId);
        if ($existing !== null) {
            return $existing;
        }

        return $this->repository->createSession($callLog, $supervisorId, $payload);
    }

    public function stopSession(CallMonitorLog $monitorLog, int $supervisorId, ?string $notes = null): CallMonitorLog
    {
        if ($monitorLog->supervisor_id !== $supervisorId) {
            throw new InvalidArgumentException('Only the monitoring supervisor can stop this session.');
        }

        if ($monitorLog->status->value !== 'ACTIVE') {
            return $monitorLog;
        }

        return $this->repository->stopSession($monitorLog, $notes);
    }
}
