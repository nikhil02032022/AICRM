<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Counselling;

use App\DTOs\CRM\UpdateAssignmentConfigDTO;
use App\Enums\CRM\AssignmentMode;
use App\Models\CRM\CounsellorAssignmentConfig;

// BRD: CRM-EC-006 — Eloquent implementation
final class EloquentCounsellorAssignmentConfigRepository implements CounsellorAssignmentConfigRepositoryInterface
{
    public function getOrCreateForInstitution(int $institutionId): CounsellorAssignmentConfig
    {
        return CounsellorAssignmentConfig::withoutGlobalScopes()
            ->firstOrCreate(
                ['institution_id' => $institutionId],
                [
                    'assignment_mode' => AssignmentMode::ROUND_ROBIN->value,
                    'max_leads_per_counsellor' => 50,
                    'escalation_hours' => 24,
                ],
            );
    }

    public function update(
        CounsellorAssignmentConfig $config,
        UpdateAssignmentConfigDTO $dto,
    ): CounsellorAssignmentConfig {
        $config->update([
            'assignment_mode' => $dto->assignmentMode->value,
            'max_leads_per_counsellor' => $dto->maxLeadsPerCounsellor,
            'escalation_hours' => $dto->escalationHours,
            'escalation_to_user_id' => $dto->escalationToUserId,
        ]);

        return $config->fresh();
    }

    public function advanceRoundRobinPointer(CounsellorAssignmentConfig $config, int $counsellorId): void
    {
        CounsellorAssignmentConfig::withoutGlobalScopes()
            ->where('id', $config->id)
            ->update(['round_robin_pointer_user_id' => $counsellorId]);
    }
}
