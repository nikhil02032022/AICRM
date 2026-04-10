<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Scoring;

use App\Models\CRM\InstitutionScoringConfig;
use Illuminate\Support\Str;

// BRD: CRM-LQ-001, CRM-LQ-005
final class EloquentScoringConfigRepository implements ScoringConfigRepositoryInterface
{
    public function findByInstitution(int $institutionId): ?InstitutionScoringConfig
    {
        return InstitutionScoringConfig::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(int $institutionId, array $data): InstitutionScoringConfig
    {
        $existing = $this->findByInstitution($institutionId);

        if ($existing !== null) {
            $existing->update($data);

            return $existing->fresh();
        }

        return InstitutionScoringConfig::withoutGlobalScopes()->create(array_merge(
            ['uuid' => Str::uuid()->toString(), 'institution_id' => $institutionId],
            $data,
        ));
    }
}
