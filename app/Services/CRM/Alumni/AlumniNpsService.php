<?php

declare(strict_types=1);

namespace App\Services\CRM\Alumni;

use App\Enums\CRM\Alumni\NpsSnapshotSource;
use App\Models\CRM\Alumni\AlumniNpsSnapshot;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

// BRD: CRM-AL-004 — NPS snapshot persistence and trend retrieval for analytics dashboard
final class AlumniNpsService
{
    // BRD: CRM-AL-004 — Store an NPS snapshot (manual or webhook); validates percentages sum to 100
    public function storeSnapshot(array $validated): AlumniNpsSnapshot
    {
        $total = (float) $validated['promoters_pct']
               + (float) $validated['neutrals_pct']
               + (float) $validated['detractors_pct'];

        if (abs($total - 100.0) > 0.01) {
            throw ValidationException::withMessages([
                'promoters_pct' => ['Promoters, Neutrals and Detractors percentages must sum to 100%.'],
            ]);
        }

        $npsScore = (int) round((float) $validated['promoters_pct'] - (float) $validated['detractors_pct']);

        return AlumniNpsSnapshot::create([
            'institution_id'   => $validated['institution_id'],
            'academic_year_id' => $validated['academic_year_id'],
            'programme_id'     => $validated['programme_id'] ?? null,
            'nps_score'        => $npsScore,
            'promoters_pct'    => $validated['promoters_pct'],
            'neutrals_pct'     => $validated['neutrals_pct'],
            'detractors_pct'   => $validated['detractors_pct'],
            'survey_date'      => $validated['survey_date'],
            'source'           => $validated['source'] ?? NpsSnapshotSource::Manual->value,
        ]);
    }

    // BRD: CRM-AL-004 — Latest NPS snapshot by survey_date for the given institution
    public function getLatestScore(int $institutionId): ?AlumniNpsSnapshot
    {
        return AlumniNpsSnapshot::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->orderByDesc('survey_date')
            ->first();
    }

    // BRD: CRM-AL-004 — NPS trend (last N months) for sparkline chart on executive dashboard
    public function getTrend(int $institutionId, int $months = 12): Collection
    {
        return AlumniNpsSnapshot::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('survey_date', '>=', now()->subMonths($months)->startOfMonth())
            ->orderBy('survey_date')
            ->get();
    }
}
