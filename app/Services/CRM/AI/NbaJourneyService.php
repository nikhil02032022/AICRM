<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Models\CRM\Lead;
use App\Models\CRM\NbaJourney;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

// BRD: CRM-AI-010 — Generate segment-wise nurture journey suggestions for marketing automation handoff
final class NbaJourneyService
{
    /** @return Collection<int, NbaJourney> */
    public function generateForInstitution(int $institutionId, Carbon $forDate, ?string $segment = null): Collection
    {
        $segments = $this->segmentDefinitions();

        if ($segment !== null && isset($segments[$segment])) {
            $segments = [$segment => $segments[$segment]];
        }

        NbaJourney::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereDate('generated_for_date', $forDate->toDateString())
            ->where('model_version', 'a2a-nba-journey-rules-v1')
            ->when($segment !== null, fn ($query) => $query->where('segment_key', $segment))
            ->delete();

        $rows = collect();

        foreach ($segments as $segmentKey => $definition) {
            $query = Lead::withoutGlobalScopes()
                ->where('institution_id', $institutionId);

            $definition['filter']($query);
            $total = (clone $query)->count();

            if ($total === 0) {
                continue;
            }

            $recent = (clone $query)
                ->whereDate('updated_at', '>=', $forDate->copy()->subDays(7)->toDateString())
                ->count();

            $confidence = $this->confidenceScore($total, $recent);
            $steps = $definition['steps'];

            $rows->push(NbaJourney::withoutGlobalScopes()->create([
                'uuid' => (string) Str::uuid(),
                'institution_id' => $institutionId,
                'segment_key' => $segmentKey,
                'segment_label' => $definition['label'],
                'confidence_score' => $confidence,
                'rationale' => sprintf(
                    'Generated nurture journey for %s using %d matching leads and %d recently active leads.',
                    $definition['label'],
                    $total,
                    $recent,
                ),
                'steps' => $steps,
                'metadata' => [
                    'lead_count' => $total,
                    'recently_active' => $recent,
                    'recommended_channels' => array_values(array_unique(array_map(
                        static fn (array $step): string => (string) ($step['channel'] ?? 'email'),
                        $steps,
                    ))),
                ],
                'model_version' => 'a2a-nba-journey-rules-v1',
                'generated_for_date' => $forDate->toDateString(),
                'suggested_at' => now(),
            ]));
        }

        return $rows;
    }

    /** @return array<string, array{label:string,filter:callable,steps:list<array<string, mixed>>}> */
    private function segmentDefinitions(): array
    {
        return [
            'hot_leads' => [
                'label' => 'Hot Leads',
                'filter' => static fn ($query) => $query->where('temperature', 'hot'),
                'steps' => [
                    ['day_offset' => 0, 'channel' => 'whatsapp', 'action' => 'Send high-intent counsellor intro and slot options'],
                    ['day_offset' => 1, 'channel' => 'voice', 'action' => 'Counsellor call for application closure'],
                    ['day_offset' => 2, 'channel' => 'email', 'action' => 'Share fee plan + scholarship summary'],
                ],
            ],
            'warm_leads' => [
                'label' => 'Warm Leads',
                'filter' => static fn ($query) => $query->where('temperature', 'warm'),
                'steps' => [
                    ['day_offset' => 0, 'channel' => 'email', 'action' => 'Programme-fit narrative with success stories'],
                    ['day_offset' => 2, 'channel' => 'whatsapp', 'action' => 'Prompt for questionnaire completion'],
                    ['day_offset' => 4, 'channel' => 'voice', 'action' => 'Follow-up call for objection handling'],
                ],
            ],
            'cold_or_inactive' => [
                'label' => 'Cold or Inactive Leads',
                'filter' => static fn ($query) => $query->where(static function ($segmentQuery): void {
                    $segmentQuery
                        ->whereIn('temperature', ['cold', 'lost'])
                        ->orWhereDate('updated_at', '<=', now()->subDays(14)->toDateString());
                }),
                'steps' => [
                    ['day_offset' => 0, 'channel' => 'email', 'action' => 'Re-engagement offer with deadline'],
                    ['day_offset' => 3, 'channel' => 'whatsapp', 'action' => 'Short reminder with counsellor callback CTA'],
                    ['day_offset' => 7, 'channel' => 'voice', 'action' => 'Final win-back call and close-loop note'],
                ],
            ],
            'application_started' => [
                'label' => 'Application Started',
                'filter' => static fn ($query) => $query->whereIn('status', ['application_started', 'application_submitted']),
                'steps' => [
                    ['day_offset' => 0, 'channel' => 'whatsapp', 'action' => 'Checklist reminder for pending documents'],
                    ['day_offset' => 1, 'channel' => 'email', 'action' => 'Fee and timeline explainer with FAQ'],
                    ['day_offset' => 2, 'channel' => 'voice', 'action' => 'Counsellor assistance call for submission blockers'],
                ],
            ],
        ];
    }

    private function confidenceScore(int $leadCount, int $recentlyActiveCount): int
    {
        $sampleScore = min(55, $leadCount);
        $freshnessScore = min(30, $recentlyActiveCount * 3);

        return max(40, min(95, $sampleScore + $freshnessScore + 10));
    }
}
