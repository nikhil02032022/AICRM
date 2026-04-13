<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Models\CRM\AiLeadScore;
use App\Models\CRM\Lead;
use Illuminate\Support\Str;

// BRD: CRM-LQ-003 — AI-assisted scoring service with auditable rationale persistence
final class AiLeadScoringService
{
    public function calculateAndPersist(Lead $lead): AiLeadScore
    {
        $signalCount = 0;

        if ($lead->consent_given) {
            $signalCount += 1;
        }

        if ($lead->programmeInterests()->exists()) {
            $signalCount += 1;
        }

        if ($lead->assigned_counsellor_id !== null) {
            $signalCount += 1;
        }

        if ($lead->questionnaireResponses()->exists()) {
            $signalCount += 1;
        }

        $baseline = (int) $lead->lead_score;
        $aiBoost = min(20, $signalCount * 5);
        $aiScore = min(100, max(0, $baseline + $aiBoost));

        $explanation = sprintf(
            'AI score derived from rule score %d with %d supporting qualification signals.',
            $baseline,
            $signalCount,
        );

        return AiLeadScore::withoutGlobalScopes()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $lead->institution_id,
            'campus_id' => $lead->campus_id,
            'lead_id' => $lead->id,
            'score' => $aiScore,
            'explanation' => $explanation,
            'model_version' => (string) config('services.anthropic.model', 'a2a-heuristic-v1'),
            'metadata' => [
                'baseline_score' => $baseline,
                'signal_count' => $signalCount,
                'source' => 'heuristic_fallback',
            ],
            'calculated_at' => now(),
        ]);
    }
}
