<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Marketing;

use App\DTOs\CRM\CreateChatLeadDTO;
use App\Models\CRM\ChatLead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ChatLeadRepositoryInterface
{
    public function create(CreateChatLeadDTO $dto, int $institutionId, int $leadId, string $ip): ChatLead;

    public function findByUuidOrFail(string $uuid): ChatLead;

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function update(ChatLead $chatLead, array $data): ChatLead;

    public function appendTranscriptMessage(ChatLead $chatLead, string $role, string $content): ChatLead;

    public function updateHandoffStatus(ChatLead $chatLead, string $status, ?int $assignedTo = null): ChatLead;

    /** @return array<string, int|float> */
    public function metrics(int $institutionId): array;
}
