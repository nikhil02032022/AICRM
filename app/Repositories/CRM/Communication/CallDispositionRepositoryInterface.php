<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Communication;

use App\Models\CRM\CallDispositionConfig;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CallDispositionRepositoryInterface
{
    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator;

    /** @return Collection<int, CallDispositionConfig> */
    public function activeForInstitution(int $institutionId): Collection;

    public function findByCode(int $institutionId, string $code): ?CallDispositionConfig;

    /** @param array<string, mixed> $payload */
    public function create(int $institutionId, int $createdBy, array $payload): CallDispositionConfig;

    /** @param array<string, mixed> $payload */
    public function update(CallDispositionConfig $config, array $payload): CallDispositionConfig;
}
