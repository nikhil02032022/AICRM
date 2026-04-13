<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Models\CRM\AiLeadScore;
use App\Models\CRM\CounsellorPriorityLead;
use App\Models\CRM\Lead;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

// BRD: CRM-AI-005 — Generate daily counsellor lead priorities using score, inactivity, and conversion probability
final class PriorityLeadListService
{
    public function generateForInstitution(int $institutionId, Carbon $forDate): int
    {
        $leads = Lead::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereNotNull('assigned_counsellor_id')
            ->select(['id', 'uuid', 'institution_id', 'campus_id', 'assigned_counsellor_id', 'lead_score', 'updated_at'])
            ->get();

        CounsellorPriorityLead::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereDate('generated_for_date', $forDate->toDateString())
            ->delete();

        $rows = [];

        foreach ($leads->groupBy('assigned_counsellor_id') as $counsellorId => $items) {
            $ranked = $this->rankCounsellorLeads($items);
            foreach ($ranked as $index => $entry) {
                /** @var Lead $lead */
                $lead = $entry['lead'];
                $rows[] = [
                    'uuid' => (string) Str::uuid(),
                    'institution_id' => $institutionId,
                    'campus_id' => $lead->campus_id,
                    'counsellor_id' => (int) $counsellorId,
                    'lead_id' => $lead->id,
                    'priority_rank' => $index + 1,
                    'priority_score' => $entry['priority_score'],
                    'reasoning' => $entry['reasoning'],
                    'factors' => json_encode($entry['factors']) ?: '{}',
                    'generated_for_date' => $forDate->toDateString(),
                    'generated_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($rows !== []) {
            CounsellorPriorityLead::withoutGlobalScopes()->insert($rows);
        }

        return count($rows);
    }

    /** @param Collection<int, Lead> $leads */
    private function rankCounsellorLeads(Collection $leads): array
    {
        $scored = $leads->map(function (Lead $lead): array {
            $latestAiScore = AiLeadScore::withoutGlobalScopes()
                ->where('lead_id', $lead->id)
                ->latest('calculated_at')
                ->value('score');

            $baseScore = max(0, min(100, (int) $lead->lead_score));
            $conversionProbability = max(0, min(100, (int) ($latestAiScore ?? $baseScore)));
            $inactiveDays = (int) ($lead->updated_at?->diffInDays(now()) ?? 0);
            $inactivityScore = max(0, min(100, (int) round((min($inactiveDays, 21) / 21) * 100)));

            $priorityScore = (int) round(
                ($baseScore * 0.45) +
                ($inactivityScore * 0.30) +
                ($conversionProbability * 0.25)
            );

            return [
                'lead' => $lead,
                'priority_score' => max(0, min(100, $priorityScore)),
                'reasoning' => sprintf(
                    'Priority derived from score %d, inactivity %d days, conversion probability %d%%.',
                    $baseScore,
                    $inactiveDays,
                    $conversionProbability,
                ),
                'factors' => [
                    'lead_score' => $baseScore,
                    'inactivity_days' => $inactiveDays,
                    'inactivity_score' => $inactivityScore,
                    'conversion_probability' => $conversionProbability,
                ],
            ];
        });

        return $scored
            ->sortByDesc('priority_score')
            ->values()
            ->all();
    }
}
