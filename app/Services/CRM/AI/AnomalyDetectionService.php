<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Models\CRM\AnomalyAlert;
use App\Models\CRM\Lead;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

// BRD: CRM-AI-009 — Detect lead funnel drop-off anomalies against rolling baseline windows
final class AnomalyDetectionService
{
    /** @return Collection<int, AnomalyAlert> */
    public function detectForInstitution(
        int $institutionId,
        Carbon $forDate,
        int $windowDays = 7,
        int $baselineDays = 28,
        int $thresholdPercent = 25,
    ): Collection {
        $end = $forDate->copy()->endOfDay();
        $currentStart = $end->copy()->subDays($windowDays - 1)->startOfDay();
        $baselineEnd = $currentStart->copy()->subDay()->endOfDay();
        $baselineStart = $baselineEnd->copy()->subDays($baselineDays - 1)->startOfDay();

        $metrics = [
            'lead_volume' => [
                'current' => $this->leadCount($institutionId, $currentStart, $end),
                'baseline' => $this->leadCount($institutionId, $baselineStart, $baselineEnd),
            ],
            'application_submitted_volume' => [
                'current' => $this->applicationSubmittedCount($institutionId, $currentStart, $end),
                'baseline' => $this->applicationSubmittedCount($institutionId, $baselineStart, $baselineEnd),
            ],
        ];

        AnomalyAlert::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereBetween('detected_at', [$currentStart->toDateTimeString(), $end->toDateTimeString()])
            ->where('model_version', 'a2a-anomaly-rules-v1')
            ->delete();

        $alerts = collect();

        foreach ($metrics as $metric => $values) {
            if ($values['baseline'] <= 0) {
                continue;
            }

            $deviationPercent = round((($values['current'] - $values['baseline']) / $values['baseline']) * 100, 2);

            if ($deviationPercent > -$thresholdPercent) {
                continue;
            }

            $severity = $this->resolveSeverity($deviationPercent);
            $rationale = sprintf(
                'Detected %.2f%% drop for %s: current window %d vs baseline %d.',
                $deviationPercent,
                str_replace('_', ' ', $metric),
                $values['current'],
                $values['baseline'],
            );

            $alerts->push(AnomalyAlert::withoutGlobalScopes()->create([
                'uuid' => (string) Str::uuid(),
                'institution_id' => $institutionId,
                'alert_type' => 'drop_off',
                'metric_name' => $metric,
                'current_value' => $values['current'],
                'baseline_value' => $values['baseline'],
                'deviation_percent' => $deviationPercent,
                'threshold_percent' => $thresholdPercent,
                'severity' => $severity,
                'rationale' => $rationale,
                'metadata' => [
                    'window_days' => $windowDays,
                    'baseline_days' => $baselineDays,
                    'current_window_start' => $currentStart->toDateString(),
                    'current_window_end' => $end->toDateString(),
                    'baseline_window_start' => $baselineStart->toDateString(),
                    'baseline_window_end' => $baselineEnd->toDateString(),
                ],
                'model_version' => 'a2a-anomaly-rules-v1',
                'detected_at' => now(),
            ]));
        }

        return $alerts;
    }

    private function leadCount(int $institutionId, Carbon $start, Carbon $end): int
    {
        return Lead::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->count();
    }

    private function applicationSubmittedCount(int $institutionId, Carbon $start, Carbon $end): int
    {
        return Lead::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('status', 'application_submitted')
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->count();
    }

    private function resolveSeverity(float $deviationPercent): string
    {
        return match (true) {
            $deviationPercent <= -60 => 'critical',
            $deviationPercent <= -40 => 'high',
            default => 'medium',
        };
    }
}
