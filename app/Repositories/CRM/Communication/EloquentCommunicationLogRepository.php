<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Communication;

use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\MessageStatus;
use App\Models\CRM\CommunicationLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentCommunicationLogRepository implements CommunicationLogRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): CommunicationLog
    {
        return CommunicationLog::create($data);
    }

    public function findByExternalId(string $externalId): ?CommunicationLog
    {
        return CommunicationLog::where('external_id', $externalId)->first();
    }

    /** @param array<string, mixed> $data */
    public function update(CommunicationLog $log, array $data): CommunicationLog
    {
        $log->update($data);

        return $log->fresh();
    }

    /** @param array<string, mixed> $filters */
    public function paginateForLead(int $leadId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = CommunicationLog::where('lead_id', $leadId);

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function countUnreadForCounsellor(int $userId, CommunicationChannel $channel): int
    {
        return CommunicationLog::where('channel', $channel)
            ->where('direction', MessageDirection::INBOUND)
            ->whereNull('opened_at')
            ->whereHas('lead', fn ($q) => $q->where('assigned_counsellor_id', $userId))
            ->count();
    }
}
