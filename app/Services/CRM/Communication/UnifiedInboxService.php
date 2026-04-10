<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\MessageDirection;
use App\Models\CRM\CommunicationLog;
use App\Models\User;
use App\Repositories\CRM\Communication\CommunicationLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// BRD: CRM-CC-021, CRM-CC-022 — Unified inbox consolidating all channels per counsellor
final class UnifiedInboxService
{
    public function __construct(
        private readonly CommunicationLogRepositoryInterface $logRepository,
    ) {}

    /**
     * BRD: CRM-CC-021 — Fetch paginated inbound messages across all channels for assigned counsellor.
     *
     * @param array<string, mixed> $filters
     */
    public function getInboxForCounsellor(User $counsellor, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = CommunicationLog::with(['lead', 'template'])
            ->where('direction', MessageDirection::INBOUND)
            ->whereHas('lead', fn ($q) => $q->where('assigned_counsellor_id', $counsellor->id));

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (! empty($filters['unread'])) {
            $query->whereNull('opened_at');
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * BRD: CRM-CC-021 — Mark a message as read (by channel + entity ID).
     */
    public function markAsRead(string $channel, int $logId, User $user): void
    {
        CommunicationLog::where('id', $logId)
            ->whereNull('opened_at')
            ->update(['opened_at' => now()]);
    }

    /**
     * BRD: CRM-CC-021 — Get unread counts per channel for the given counsellor.
     *
     * @return array<string, int>
     */
    public function getUnreadCounts(User $counsellor): array
    {
        $counts = [];

        foreach ([CommunicationChannel::EMAIL, CommunicationChannel::SMS, CommunicationChannel::WHATSAPP] as $channel) {
            $counts[$channel->value] = $this->logRepository->countUnreadForCounsellor($counsellor->id, $channel);
        }

        return $counts;
    }
}
