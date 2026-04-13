<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Models\CRM\CrmProgramme;
use App\Models\CRM\EnrolmentForecast;
use App\Models\CRM\Lead;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

// BRD: CRM-AI-008 — Predictive enrolment forecasting by programme and admission cycle
final class ForecastingService
{
    /** @return Collection<int, EnrolmentForecast> */
    public function generateForInstitution(int $institutionId, Carbon $forMonth): Collection
    {
        $monthStart = $forMonth->copy()->startOfMonth();
        $admissionCycle = (string) $monthStart->year;

        EnrolmentForecast::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereDate('generated_for_month', $monthStart->toDateString())
            ->delete();

        $programmes = CrmProgramme::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->select(['id', 'name'])
            ->get();

        $rows = [];

        foreach ($programmes as $programme) {
            $stats = $this->programmePipelineStats($institutionId, (int) $programme->id);

            $forecastCount = $this->estimateForecastCount($stats);
            $confidenceScore = $this->estimateConfidenceScore($stats);

            $rows[] = [
                'uuid' => (string) Str::uuid(),
                'institution_id' => $institutionId,
                'campus_id' => null,
                'crm_programme_id' => $programme->id,
                'admission_cycle' => $admissionCycle,
                'forecast_count' => $forecastCount,
                'confidence_score' => $confidenceScore,
                'inputs' => json_encode($stats) ?: '{}',
                'model_version' => 'a2a-forecast-rules-v1',
                'generated_for_month' => $monthStart->toDateString(),
                'generated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            EnrolmentForecast::withoutGlobalScopes()->insert($rows);
        }

        return EnrolmentForecast::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereDate('generated_for_month', $monthStart->toDateString())
            ->with('programme:id,name')
            ->orderByDesc('forecast_count')
            ->get();
    }

    /** @return array{total_interest:int,recent_interest:int,pipeline_ready:int,enrolled:int,conversion_probability:float,momentum:float} */
    private function programmePipelineStats(int $institutionId, int $programmeId): array
    {
        $base = Lead::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereHas('programmeInterests', static fn ($query) => $query->where('crm_programmes.id', $programmeId));

        $totalInterest = (clone $base)->count();
        $recentInterest = (clone $base)->whereDate('created_at', '>=', now()->subDays(30)->toDateString())->count();

        $pipelineReady = (clone $base)
            ->whereIn('status', ['application_submitted', 'offer_issued', 'fee_paid', 'enrolled'])
            ->count();

        $enrolled = (clone $base)->where('status', 'enrolled')->count();

        $conversionProbability = $pipelineReady > 0
            ? round($enrolled / $pipelineReady, 2)
            : 0.35;

        $olderWindow = (clone $base)
            ->whereDate('created_at', '>=', now()->subDays(60)->toDateString())
            ->whereDate('created_at', '<', now()->subDays(30)->toDateString())
            ->count();

        $momentum = $olderWindow > 0
            ? round($recentInterest / $olderWindow, 2)
            : ($recentInterest > 0 ? 1.2 : 1.0);

        return [
            'total_interest' => $totalInterest,
            'recent_interest' => $recentInterest,
            'pipeline_ready' => $pipelineReady,
            'enrolled' => $enrolled,
            'conversion_probability' => $conversionProbability,
            'momentum' => $momentum,
        ];
    }

    /** @param array{total_interest:int,recent_interest:int,pipeline_ready:int,enrolled:int,conversion_probability:float,momentum:float} $stats */
    private function estimateForecastCount(array $stats): int
    {
        $conversionSignal = max(0.25, min(0.90, $stats['conversion_probability'] + (($stats['momentum'] - 1.0) * 0.10)));
        $base = max(0, $stats['pipeline_ready']);

        return max(0, (int) round($base * $conversionSignal));
    }

    /** @param array{total_interest:int,recent_interest:int,pipeline_ready:int,enrolled:int,conversion_probability:float,momentum:float} $stats */
    private function estimateConfidenceScore(array $stats): int
    {
        $sampleScore = min(50, $stats['total_interest']);
        $pipelineScore = min(30, $stats['pipeline_ready'] * 3);
        $stabilityScore = (int) round(max(0.0, 20 - (abs($stats['momentum'] - 1.0) * 10)));

        return max(30, min(95, $sampleScore + $pipelineScore + $stabilityScore));
    }
}
