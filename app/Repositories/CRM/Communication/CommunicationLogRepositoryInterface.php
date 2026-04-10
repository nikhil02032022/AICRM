<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Communication;

use App\Enums\CRM\CommunicationChannel;
use App\Models\CRM\CommunicationLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CommunicationLogRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): CommunicationLog;

    public function findByExternalId(string $externalId): ?CommunicationLog;

    /** @param array<string, mixed> $data */
    public function update(CommunicationLog $log, array $data): CommunicationLog;

    /** @param array<string, mixed> $filters */
    public function paginateForLead(int $leadId, array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function countUnreadForCounsellor(int $userId, CommunicationChannel $channel): int;
}
