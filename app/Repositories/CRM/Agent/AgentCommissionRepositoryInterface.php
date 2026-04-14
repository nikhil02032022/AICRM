<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Agent;

use App\Models\CRM\AgentCommission;
use Illuminate\Pagination\LengthAwarePaginator;

// BRD: CRM-AG-006 — Agent commission repository interface
interface AgentCommissionRepositoryInterface
{
    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator;

    public function forAgent(int $agentUserId, int $institutionId, int $perPage = 20): LengthAwarePaginator;

    public function findByUuid(string $uuid): ?AgentCommission;

    public function create(array $data): AgentCommission;

    public function update(AgentCommission $commission, array $data): AgentCommission;
}
