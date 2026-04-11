<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Marketing;

use App\DTOs\CRM\CreateChatLeadDTO;
use App\Models\CRM\ChatLead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentChatLeadRepository implements ChatLeadRepositoryInterface
{
    public function create(CreateChatLeadDTO $dto, int $institutionId, int $leadId, string $ip): ChatLead
    {
        $transcript = $dto->transcript ?? [];

        $inboundCount = collect($transcript)->where('role', 'user')->count();
        $outboundCount = collect($transcript)->where('role', 'assistant')->count();

        return ChatLead::create([
            'institution_id' => $institutionId,
            'campus_id' => $dto->campusId,
            'lead_id' => $leadId,
            'session_id' => $dto->sessionId,
            'handoff_status' => 'captured',
            'visitor_name' => trim($dto->firstName.' '.$dto->lastName),
            'source_url' => $dto->sourceUrl,
            'transcript' => $transcript,
            'attribution_params' => $dto->sourceUtmParams,
            'consent_given' => $dto->consentGiven,
            'consent_timestamp' => $dto->consentGiven ? now() : null,
            'consent_ip' => $ip,
            'consent_form_version' => $dto->consentFormVersion,
            'metadata' => $dto->metadata,
            'last_message_at' => now(),
            'inbound_messages' => $inboundCount > 0 ? $inboundCount : 1,
            'outbound_messages' => $outboundCount,
        ]);
    }

    public function findByUuidOrFail(string $uuid): ChatLead
    {
        return ChatLead::where('uuid', $uuid)->firstOrFail();
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = ChatLead::query()->with([
            'lead:id,uuid,first_name,last_name,source',
            'assignedTo:id,name,email',
        ]);

        if (!empty($filters['session_id'])) {
            $query->where('session_id', 'like', '%'.$filters['session_id'].'%');
        }

        if (!empty($filters['lead_uuid'])) {
            $query->whereHas('lead', fn ($leadQuery) => $leadQuery->where('uuid', $filters['lead_uuid']));
        }

        if (!empty($filters['handoff_status'])) {
            $query->where('handoff_status', (string) $filters['handoff_status']);
        }

        return $query->latest('created_at')->paginate($perPage);
    }

    public function update(ChatLead $chatLead, array $data): ChatLead
    {
        $chatLead->update($data);

        return $chatLead->refresh();
    }

    public function appendTranscriptMessage(ChatLead $chatLead, string $role, string $content): ChatLead
    {
        $transcript = is_array($chatLead->transcript) ? $chatLead->transcript : [];

        $transcript[] = [
            'role' => $role,
            'content' => $content,
        ];

        $updates = [
            'transcript' => $transcript,
            'last_message_at' => now(),
        ];

        if ($role === 'assistant') {
            $updates['outbound_messages'] = (int) $chatLead->outbound_messages + 1;
            $updates['first_response_at'] = $chatLead->first_response_at ?? now();
            $updates['handoff_status'] = 'live_agent';
        } else {
            $updates['inbound_messages'] = (int) $chatLead->inbound_messages + 1;
        }

        return $this->update($chatLead, $updates);
    }

    public function updateHandoffStatus(ChatLead $chatLead, string $status, ?int $assignedTo = null): ChatLead
    {
        $updates = [
            'handoff_status' => $status,
        ];

        if ($assignedTo !== null) {
            $updates['assigned_to'] = $assignedTo;
        }

        return $this->update($chatLead, $updates);
    }

    public function metrics(int $institutionId): array
    {
        $baseQuery = ChatLead::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereNull('deleted_at');

        $captured = (clone $baseQuery)->count();
        $liveAgent = (clone $baseQuery)->where('handoff_status', 'live_agent')->count();
        $resolved = (clone $baseQuery)->where('handoff_status', 'resolved')->count();
        $pending = (clone $baseQuery)->whereIn('handoff_status', ['captured', 'pending_agent'])->count();

        $avgMinutes = (clone $baseQuery)
            ->whereNotNull('first_response_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_minutes')
            ->value('avg_minutes');

        return [
            'captured_count' => $captured,
            'live_agent_count' => $liveAgent,
            'resolved_count' => $resolved,
            'pending_count' => $pending,
            'avg_first_response_minutes' => $avgMinutes !== null ? (float) $avgMinutes : 0.0,
        ];
    }
}
