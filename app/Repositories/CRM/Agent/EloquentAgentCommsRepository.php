<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Agent;

use App\Models\CRM\AgentCommsLog;
use Illuminate\Pagination\LengthAwarePaginator;

// BRD: CRM-AG-008 — Eloquent implementation of agent bulk comms log repository
final class EloquentAgentCommsRepository implements AgentCommsRepositoryInterface
{
    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return AgentCommsLog::where('institution_id', $institutionId)
            ->with('sentBy')
            ->latest()
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?AgentCommsLog
    {
        return AgentCommsLog::where('uuid', $uuid)->with('sentBy')->first();
    }

    public function create(array $data): AgentCommsLog
    {
        return AgentCommsLog::create($data);
    }

    public function update(AgentCommsLog $log, array $data): AgentCommsLog
    {
        $log->update($data);

        return $log->refresh();
    }
}
