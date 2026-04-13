<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Communication;

use App\Enums\CRM\CallMonitorStatus;
use App\Enums\CRM\CallStatus;
use App\Models\CRM\CallLog;
use App\Models\CRM\CallMonitorLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class EloquentCallMonitorRepository implements CallMonitorRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return CallMonitorLog::query()
            ->with(['callLog.lead', 'supervisor'])
            ->when(($filters['status'] ?? null) !== null && $filters['status'] !== '', function ($query) use ($filters): void {
                $query->where('status', (string) $filters['status']);
            })
            ->when(($filters['mode'] ?? null) !== null && $filters['mode'] !== '', function ($query) use ($filters): void {
                $query->where('mode', (string) $filters['mode']);
            })
            ->latest('started_at')
            ->paginate($perPage);
    }

    public function createSession(CallLog $callLog, int $supervisorId, array $payload): CallMonitorLog
    {
        return CallMonitorLog::create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $callLog->institution_id,
            'campus_id' => $callLog->campus_id,
            'call_log_id' => $callLog->id,
            'supervisor_id' => $supervisorId,
            'mode' => $payload['mode'],
            'status' => CallMonitorStatus::ACTIVE,
            'provider_session_id' => 'MON-'.strtoupper(Str::random(12)),
            'consent_validated' => (bool) $callLog->call_consent_given,
            'started_at' => now(),
            'notes' => $payload['notes'] ?? null,
        ]);
    }

    public function stopSession(CallMonitorLog $monitorLog, ?string $notes = null): CallMonitorLog
    {
        $startedAt = $monitorLog->started_at ?? now();
        $endedAt = now();

        $monitorLog->update([
            'status' => CallMonitorStatus::ENDED,
            'ended_at' => $endedAt,
            'duration_seconds' => $startedAt->diffInSeconds($endedAt),
            'notes' => $notes ?? $monitorLog->notes,
        ]);

        return $monitorLog->fresh(['callLog.lead', 'supervisor']);
    }

    public function findActiveSession(int $callLogId, int $supervisorId): ?CallMonitorLog
    {
        return CallMonitorLog::query()
            ->where('call_log_id', $callLogId)
            ->where('supervisor_id', $supervisorId)
            ->where('status', CallMonitorStatus::ACTIVE)
            ->first();
    }

    public function activeCalls(int $limit = 30): LengthAwarePaginator
    {
        return CallLog::query()
            ->with(['lead', 'initiatedBy'])
            ->whereIn('status', [
                CallStatus::INITIATED,
                CallStatus::RINGING,
                CallStatus::IN_PROGRESS,
            ])
            ->latest('called_at')
            ->paginate($limit);
    }
}
