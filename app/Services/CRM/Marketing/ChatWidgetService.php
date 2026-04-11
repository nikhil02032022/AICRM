<?php

declare(strict_types=1);

namespace App\Services\CRM\Marketing;

use App\DTOs\CRM\CreateChatLeadDTO;
use App\DTOs\CRM\CreateLeadDTO;
use App\Enums\CRM\LeadSource;
use App\Events\CRM\ChatLeadCreatedEvent;
use App\Jobs\CRM\ProcessChatLeadJob;
use App\Models\CRM\ChatLead;
use App\Models\User;
use App\Repositories\CRM\Marketing\ChatLeadRepositoryInterface;
use App\Services\CRM\Lead\LeadService;
use App\Services\CRM\WebForm\PublicFormActor;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LC-006 — Orchestrates chat transcript capture and lead creation
final class ChatWidgetService
{
    public function __construct(
        private readonly LeadService $leadService,
        private readonly ChatLeadRepositoryInterface $repository,
    ) {}

    public function captureLead(CreateChatLeadDTO $dto, int $institutionId, string $ip): ChatLead
    {
        $lead = $this->leadService->create(
            new CreateLeadDTO(
                firstName: $dto->firstName,
                lastName: $dto->lastName,
                mobile: $dto->mobile,
                email: $dto->email,
                source: LeadSource::LIVE_CHAT->value,
                consentGiven: $dto->consentGiven,
                consentIp: $ip,
                consentFormVersion: $dto->consentFormVersion,
                campusId: $dto->campusId,
                city: null,
                state: null,
                notes: $this->buildLeadNote($dto),
                sourceUtmParams: $dto->sourceUtmParams,
                programmeIds: null,
            ),
            new PublicFormActor($institutionId),
        );

        $chatLead = $this->repository->create($dto, $institutionId, $lead->id, $ip);

        Log::info('Chat lead created', [
            'chat_lead_uuid' => $chatLead->uuid,
            'lead_uuid' => $lead->uuid,
            'institution_id' => $institutionId,
        ]);

        ChatLeadCreatedEvent::dispatch($chatLead);
        ProcessChatLeadJob::dispatch($chatLead->uuid);

        return $chatLead->loadMissing('lead');
    }

    /** @param array<string, mixed> $filters */
    public function list(array $filters, int $perPage = 20)
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function appendStaffReply(ChatLead $chatLead, string $message, User $actor): ChatLead
    {
        $trimmed = trim($message);

        $updated = $this->repository->appendTranscriptMessage($chatLead, 'assistant', $trimmed);

        return $this->repository->update($updated, [
            'assigned_to' => $actor->id,
            'processed_at' => $updated->processed_at ?? now(),
        ]);
    }

    public function updateHandoffStatus(ChatLead $chatLead, string $status, ?int $assignedTo = null): ChatLead
    {
        return $this->repository->updateHandoffStatus($chatLead, $status, $assignedTo);
    }

    /** @return array<string, int|float> */
    public function metrics(int $institutionId): array
    {
        return $this->repository->metrics($institutionId);
    }

    private function buildLeadNote(CreateChatLeadDTO $dto): string
    {
        $firstMessage = collect($dto->transcript ?? [])->firstWhere('role', 'user')['content'] ?? null;

        return $firstMessage !== null && $firstMessage !== ''
            ? 'Live chat enquiry: '.mb_substr($firstMessage, 0, 280)
            : 'Live chat enquiry captured via website widget.';
    }
}
