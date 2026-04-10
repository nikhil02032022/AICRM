<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Counselling;

use App\DTOs\CRM\UpdateAssignmentConfigDTO;
use App\Models\CRM\CounsellorAssignmentConfig;

// BRD: CRM-EC-006 — Repository interface for counsellor assignment configuration
interface CounsellorAssignmentConfigRepositoryInterface
{
    /**
     * Get or create the assignment config for a given institution.
     * Returns sensible defaults on first call.
     */
    public function getOrCreateForInstitution(int $institutionId): CounsellorAssignmentConfig;

    public function update(
        CounsellorAssignmentConfig $config,
        UpdateAssignmentConfigDTO $dto,
    ): CounsellorAssignmentConfig;

    /** Advance the round-robin pointer to the given counsellor's user ID. */
    public function advanceRoundRobinPointer(CounsellorAssignmentConfig $config, int $counsellorId): void;
}
