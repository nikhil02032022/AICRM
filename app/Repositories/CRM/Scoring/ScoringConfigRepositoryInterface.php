<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Scoring;

use App\Models\CRM\InstitutionScoringConfig;

// BRD: CRM-LQ-001, CRM-LQ-005 — Contract for scoring configuration persistence
interface ScoringConfigRepositoryInterface
{
    /**
     * Find the scoring config for an institution.
     * Returns null if none has been configured yet.
     */
    public function findByInstitution(int $institutionId): ?InstitutionScoringConfig;

    /**
     * Create or update the scoring config for an institution.
     *
     * @param array<string, mixed> $data
     */
    public function upsert(int $institutionId, array $data): InstitutionScoringConfig;
}
