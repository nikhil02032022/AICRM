<?php

declare(strict_types=1);

namespace App\Services\CRM\Agents;

use App\Enums\CRM\Agents\AgentStatus;
use App\Models\CRM\Agents\Agent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

// BRD: CRM-AG-001 — Agent profile CRUD and lifecycle management
final class AgentService
{
    public function list(int $institutionId, array $filters = []): LengthAwarePaginator
    {
        $query = Agent::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->orderBy('name');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                  ->orWhere('email', 'like', $term);
            });
        }

        return $query->paginate(20);
    }

    public function create(array $data): Agent
    {
        $data['password'] = Hash::make($data['password']);

        $agent = Agent::withoutGlobalScopes()->create($data);

        // Auto-generate a referral code on creation
        app(AgentReferralService::class)->generateCode($agent);

        return $agent;
    }

    public function update(Agent $agent, array $data): Agent
    {
        if (isset($data['password']) && $data['password'] !== '') {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $agent->update($data);

        return $agent->fresh();
    }

    public function deactivate(Agent $agent): void
    {
        $agent->update(['status' => AgentStatus::Inactive]);
    }

    public function search(string $query, int $institutionId): Collection
    {
        $term = '%' . $query . '%';

        return Agent::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('status', AgentStatus::Active)
            ->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
            })
            ->limit(20)
            ->get();
    }
}
