<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Communication;

use App\Models\CRM\Lead;
use App\Models\CRM\TelecallingCampaign;
use App\Models\CRM\TelecallingCampaignAgent;
use App\Models\CRM\TelecallingCampaignLead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EloquentTelecallingCampaignRepository implements TelecallingCampaignRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return TelecallingCampaign::query()
            ->with(['creator:id,name'])
            ->with(['diallerSessions:id,telecalling_campaign_id,placed_calls,queued_calls,skipped_calls,failed_calls'])
            ->withCount(['agents', 'leads', 'diallerSessions'])
            ->when(($filters['status'] ?? null) !== null && $filters['status'] !== '', function ($query) use ($filters): void {
                $query->where('status', (string) $filters['status']);
            })
            ->when(($filters['search'] ?? null) !== null && $filters['search'] !== '', function ($query) use ($filters): void {
                $query->where('name', 'like', '%'.(string) $filters['search'].'%');
            })
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function create(int $institutionId, int $createdBy, array $payload): TelecallingCampaign
    {
        return DB::transaction(function () use ($institutionId, $createdBy, $payload): TelecallingCampaign {
            $campaign = TelecallingCampaign::withoutGlobalScopes()->create([
                'uuid' => (string) Str::uuid(),
                'institution_id' => $institutionId,
                'campus_id' => $payload['campus_id'] ?? null,
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'status' => $payload['status'] ?? 'DRAFT',
                'start_time_window' => $payload['start_time_window'] ?? null,
                'end_time_window' => $payload['end_time_window'] ?? null,
                'created_by' => $createdBy,
            ]);

            $this->syncAgentsAndLeads($campaign, $payload);

            return $campaign->fresh(['agents.user', 'leads.lead']);
        });
    }

    public function update(TelecallingCampaign $campaign, array $payload): TelecallingCampaign
    {
        return DB::transaction(function () use ($campaign, $payload): TelecallingCampaign {
            $campaign->update([
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'status' => $payload['status'] ?? 'DRAFT',
                'campus_id' => $payload['campus_id'] ?? null,
                'start_time_window' => $payload['start_time_window'] ?? null,
                'end_time_window' => $payload['end_time_window'] ?? null,
            ]);

            $this->syncAgentsAndLeads($campaign, $payload);

            return $campaign->fresh(['agents.user', 'leads.lead']);
        });
    }

    /** @param array<string, mixed> $payload */
    private function syncAgentsAndLeads(TelecallingCampaign $campaign, array $payload): void
    {
        $agentIds = array_values(array_unique(array_map('intval', $payload['agent_ids'] ?? [])));
        $leadUuids = array_values(array_unique(array_map('strval', $payload['lead_uuids'] ?? [])));

        TelecallingCampaignAgent::withoutGlobalScopes()->where('telecalling_campaign_id', $campaign->id)->forceDelete();
        TelecallingCampaignLead::withoutGlobalScopes()->where('telecalling_campaign_id', $campaign->id)->forceDelete();

        foreach ($agentIds as $agentId) {
            TelecallingCampaignAgent::withoutGlobalScopes()->create([
                'uuid' => (string) Str::uuid(),
                'institution_id' => $campaign->institution_id,
                'campus_id' => $campaign->campus_id,
                'telecalling_campaign_id' => $campaign->id,
                'user_id' => $agentId,
            ]);
        }

        if ($leadUuids === []) {
            return;
        }

        $leadRows = Lead::withoutGlobalScopes()
            ->where('institution_id', $campaign->institution_id)
            ->whereIn('uuid', $leadUuids)
            ->get(['id', 'uuid', 'campus_id']);

        $agentCount = count($agentIds);
        foreach ($leadRows->values() as $index => $leadRow) {
            $assignedAgentId = $agentCount > 0 ? $agentIds[$index % $agentCount] : null;

            TelecallingCampaignLead::withoutGlobalScopes()->create([
                'uuid' => (string) Str::uuid(),
                'institution_id' => $campaign->institution_id,
                'campus_id' => $leadRow->campus_id,
                'telecalling_campaign_id' => $campaign->id,
                'lead_id' => $leadRow->id,
                'assigned_agent_id' => $assignedAgentId,
                'queue_order' => $index + 1,
            ]);
        }
    }
}
