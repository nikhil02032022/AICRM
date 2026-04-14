<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Agent;

use App\Models\CRM\AgentCommission;
use Illuminate\Pagination\LengthAwarePaginator;

// BRD: CRM-AG-006 — Eloquent implementation of agent commission repository
final class EloquentAgentCommissionRepository implements AgentCommissionRepositoryInterface
{
    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return AgentCommission::where('institution_id', $institutionId)
            ->with(['agentUser', 'lead', 'approvedBy'])
            ->latest()
            ->paginate($perPage);
    }

    public function forAgent(int $agentUserId, int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return AgentCommission::where('institution_id', $institutionId)
            ->where('agent_user_id', $agentUserId)
            ->with(['lead'])
            ->latest()
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?AgentCommission
    {
        return AgentCommission::where('uuid', $uuid)
            ->with(['agentUser', 'lead', 'approvedBy'])
            ->first();
    }

    public function create(array $data): AgentCommission
    {
        return AgentCommission::create($data);
    }

    public function update(AgentCommission $commission, array $data): AgentCommission
    {
        $commission->update($data);

        return $commission->refresh();
    }
}
