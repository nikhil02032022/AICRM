<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Lead;

use App\DTOs\CRM\CreateLeadDTO;
use App\Models\CRM\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface LeadRepositoryInterface
{
    public function create(CreateLeadDTO $dto, int $institutionId): Lead;

    public function findByUuid(string $uuid): ?Lead;

    public function findByUuidOrFail(string $uuid): Lead;

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator;

    /** @param array<string, mixed> $data */
    public function update(Lead $lead, array $data): Lead;

    public function softDelete(Lead $lead): void;

    /**
     * Find potential duplicate leads by mobile or email within the same institution.
     *
     * @return Collection<int, Lead>
     */
    public function findDuplicates(string $mobile, ?string $email, int $institutionId): Collection;

    /** @param list<int> $programmeIds */
    public function syncProgrammeInterests(Lead $lead, array $programmeIds): void;

    // BRD: CRM-EC-002 — Update pilot columns on an existing programme interest record
    /** @param array<string, mixed> $pivotData */
    public function updateProgrammeInterest(Lead $lead, int $programmeId, array $pivotData): void;

    // BRD: CRM-EC-002 — Attach a single programme interest with optional pivot data
    /** @param array<string, mixed> $pivotData */
    public function attachProgrammeInterest(Lead $lead, int $programmeId, array $pivotData): void;

    // BRD: CRM-EC-002 — Remove a single programme interest from a lead
    public function detachProgrammeInterest(Lead $lead, int $programmeId): void;
}
