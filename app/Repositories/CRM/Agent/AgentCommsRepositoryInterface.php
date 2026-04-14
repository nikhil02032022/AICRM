<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Agent;

use App\Models\CRM\AgentCommsLog;
use Illuminate\Pagination\LengthAwarePaginator;

// BRD: CRM-AG-008 — Agent bulk comms log repository interface
interface AgentCommsRepositoryInterface
{
    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?AgentCommsLog;

    public function create(array $data): AgentCommsLog;

    public function update(AgentCommsLog $log, array $data): AgentCommsLog;
}
