<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Communication;

use App\Models\CRM\CallScript;
use App\Models\CRM\CallScriptStep;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// BRD: CRM-TC-002 — Call script persistence and step resolution contract
interface CallScriptRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /** @param array<string, mixed> $payload */
    public function create(array $payload, int $institutionId, int $createdBy): CallScript;

    /** @param array<string, mixed> $payload */
    public function update(CallScript $script, array $payload): CallScript;

    public function softDelete(CallScript $script): void;

    public function findStepByKey(CallScript $script, string $stepKey): ?CallScriptStep;

    public function firstStep(CallScript $script): ?CallScriptStep;
}
