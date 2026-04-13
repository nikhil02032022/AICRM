<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Enums\CRM\ChurnRiskLevel;
use App\Enums\CRM\LeadTemperature;
use App\Models\CRM\ChurnFlag;
use App\Models\CRM\Lead;
use Illuminate\Support\Str;

// BRD: CRM-LQ-010 — Predictive churn detection service using engagement and qualification signals
final class ChurnDetectionService
{
    public function calculateAndPersist(Lead $lead): ChurnFlag
    {
        $riskScore = 0;
        $indicators = [];

        $inactiveDays = (int) $lead->updated_at?->diffInDays(now());
        if ($inactiveDays >= 14) {
            $riskScore += 40;
            $indicators['inactivity'] = '14+ days since lead update';
        } elseif ($inactiveDays >= 7) {
            $riskScore += 25;
            $indicators['inactivity'] = '7+ days since lead update';
        }

        if ((int) $lead->lead_score < 40) {
            $riskScore += 30;
            $indicators['score'] = 'rule score below 40';
        } elseif ((int) $lead->lead_score < 55) {
            $riskScore += 15;
            $indicators['score'] = 'rule score below 55';
        }

        if ($lead->assigned_counsellor_id === null) {
            $riskScore += 20;
            $indicators['ownership'] = 'no counsellor assigned';
        }

        if (! $lead->questionnaireResponses()->exists()) {
            $riskScore += 15;
            $indicators['qualification'] = 'questionnaire not filled';
        }

        if ($lead->temperature === LeadTemperature::COLD || $lead->temperature === LeadTemperature::LOST) {
            $riskScore += 20;
            $indicators['temperature'] = 'lead currently in cold/lost segment';
        }

        $riskScore = min(100, $riskScore);
        $riskLevel = $this->resolveRiskLevel($riskScore);

        $rationale = sprintf(
            'Churn risk evaluated at %d based on %d active risk indicators.',
            $riskScore,
            count($indicators),
        );

        return ChurnFlag::withoutGlobalScopes()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $lead->institution_id,
            'campus_id' => $lead->campus_id,
            'lead_id' => $lead->id,
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'rationale' => $rationale,
            'indicators' => $indicators,
            'flagged_at' => now(),
        ]);
    }

    private function resolveRiskLevel(int $riskScore): ChurnRiskLevel
    {
        return match (true) {
            $riskScore >= 70 => ChurnRiskLevel::HIGH,
            $riskScore >= 40 => ChurnRiskLevel::MEDIUM,
            default => ChurnRiskLevel::LOW,
        };
    }
}
