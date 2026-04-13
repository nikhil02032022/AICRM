<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\Enums\CRM\TelecallingCampaignStatus;
use App\Models\CRM\Lead;
use App\Models\CRM\TelecallingCampaign;
use App\Models\User;
use App\Repositories\CRM\Communication\TelecallingCampaignRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

// BRD: CRM-TC-006 — Calling campaign management orchestration
final class TelecallingCampaignService
{
    public function __construct(
        private readonly TelecallingCampaignRepositoryInterface $repository,
        private readonly DiallerService $diallerService,
    ) {}

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    /** @param array<string, mixed> $payload */
    public function create(int $institutionId, int $createdBy, array $payload): TelecallingCampaign
    {
        $this->assertTimeWindow($payload['start_time_window'] ?? null, $payload['end_time_window'] ?? null);
        $this->assertAssignableEntities($institutionId, $payload);

        return $this->repository->create($institutionId, $createdBy, $payload);
    }

    /** @param array<string, mixed> $payload */
    public function update(TelecallingCampaign $campaign, array $payload): TelecallingCampaign
    {
        $this->assertTimeWindow($payload['start_time_window'] ?? null, $payload['end_time_window'] ?? null);
        $this->assertAssignableEntities((int) $campaign->institution_id, $payload);

        return $this->repository->update($campaign, $payload);
    }

    public function launch(TelecallingCampaign $campaign): TelecallingCampaign
    {
        $start = $campaign->start_time_window;
        $end = $campaign->end_time_window;
        $now = now();

        if ($start !== null && $now->lt($start)) {
            throw new InvalidArgumentException('Campaign launch is blocked before the configured start window.');
        }

        if ($end !== null && $now->gt($end)) {
            throw new InvalidArgumentException('Campaign launch window has already ended.');
        }

        if ($campaign->status === TelecallingCampaignStatus::COMPLETED) {
            throw new InvalidArgumentException('Completed campaign cannot be launched again.');
        }

        $campaign->load(['agents.user', 'leads.lead']);

        foreach ($campaign->agents as $agentAssignment) {
            $agent = $agentAssignment->user;
            if (! $agent instanceof User) {
                continue;
            }

            $agentLeadUuids = $campaign->leads
                ->where('assigned_agent_id', $agent->id)
                ->pluck('lead.uuid')
                ->filter(static fn ($uuid) => is_string($uuid) && $uuid !== '')
                ->values()
                ->all();

            if ($agentLeadUuids === []) {
                continue;
            }

            $this->diallerService->startSession(
                user: $agent,
                leadUuids: $agentLeadUuids,
                campaignName: $campaign->name,
                leadLimit: count($agentLeadUuids),
                telecallingCampaignId: $campaign->id,
            );
        }

        $campaign->update([
            'status' => TelecallingCampaignStatus::ACTIVE,
            'launched_at' => now(),
        ]);

        return $campaign->fresh(['diallerSessions']);
    }

    /** @return array<string, int> */
    public function progress(TelecallingCampaign $campaign): array
    {
        $sessions = $campaign->diallerSessions;

        return [
            'total_leads' => (int) $campaign->leads()->count(),
            'total_agents' => (int) $campaign->agents()->count(),
            'sessions' => (int) $sessions->count(),
            'placed_calls' => (int) $sessions->sum('placed_calls'),
            'queued_calls' => (int) $sessions->sum('queued_calls'),
            'skipped_calls' => (int) $sessions->sum('skipped_calls'),
            'failed_calls' => (int) $sessions->sum('failed_calls'),
        ];
    }

    private function assertTimeWindow(mixed $start, mixed $end): void
    {
        if ($start === null || $end === null) {
            return;
        }

        $startAt = Carbon::parse((string) $start);
        $endAt = Carbon::parse((string) $end);

        if ($endAt->lte($startAt)) {
            throw new InvalidArgumentException('End time window must be after start time window.');
        }
    }

    /** @param array<string, mixed> $payload */
    private function assertAssignableEntities(int $institutionId, array $payload): void
    {
        $agentIds = array_values(array_unique(array_map('intval', $payload['agent_ids'] ?? [])));
        $leadUuids = array_values(array_unique(array_map('strval', $payload['lead_uuids'] ?? [])));

        if ($agentIds === [] || $leadUuids === []) {
            throw new InvalidArgumentException('At least one agent and one lead are required.');
        }

        $validAgentCount = User::query()
            ->where('institution_id', $institutionId)
            ->whereIn('id', $agentIds)
            ->count();

        if ($validAgentCount !== count($agentIds)) {
            throw new InvalidArgumentException('One or more selected agents are not valid for this institution.');
        }

        $validLeadCount = Lead::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereIn('uuid', $leadUuids)
            ->where('opt_out', false)
            ->whereNull('dnc_at')
            ->where('call_consent_given', true)
            ->whereNotNull('mobile')
            ->count();

        if ($validLeadCount !== count($leadUuids)) {
            throw new InvalidArgumentException('One or more selected leads are not callable or outside institution scope.');
        }
    }
}
