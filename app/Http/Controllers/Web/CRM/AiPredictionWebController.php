<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Jobs\CRM\AI\RefreshConversionPredictionJob;
use App\Models\CRM\AiLeadScore;
use App\Models\CRM\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-AI-001 — Serves conversion probability data and triggers async prediction refresh
final class AiPredictionWebController extends Controller
{
    /**
     * Return the latest conversion probability snapshot for a lead (polled by Livewire badge).
     * BRD: CRM-AI-001
     */
    public function prediction(Lead $lead): JsonResponse
    {
        Gate::authorize('ai.prediction.view', $lead);

        $latest = AiLeadScore::withoutGlobalScopes()
            ->where('lead_id', $lead->id)
            ->whereNotNull('prediction_status')
            ->latest('calculated_at')
            ->first();

        if ($latest === null) {
            return response()->json([
                'has_prediction'         => false,
                'conversion_probability' => null,
                'confidence_score'       => null,
                'confidence_level'       => null,
                'confidence_label'       => null,
                'confidence_badge_class' => null,
                'prediction_factors'     => [],
                'prediction_status'      => null,
                'prediction_refreshed_at'=> null,
                'conversion_percentage'  => null,
            ]);
        }

        $confidenceLevel = $latest->conversionConfidenceLevel();

        return response()->json([
            'has_prediction'         => true,
            'conversion_probability' => $latest->conversion_probability,
            'confidence_score'       => $latest->confidence_score,
            'confidence_level'       => $confidenceLevel?->value,
            'confidence_label'       => $confidenceLevel?->label(),
            'confidence_badge_class' => $confidenceLevel?->badgeClass(),
            'prediction_factors'     => $latest->prediction_factors ?? [],
            'prediction_status'      => $latest->prediction_status?->value,
            'prediction_refreshed_at'=> $latest->prediction_refreshed_at?->toIso8601String(),
            'conversion_percentage'  => $latest->conversionPercentage(),
        ]);
    }

    /**
     * Dispatch an async prediction refresh for the lead.
     * BRD: CRM-AI-001
     */
    public function refresh(Lead $lead): RedirectResponse
    {
        Gate::authorize('ai.prediction.view', $lead);

        RefreshConversionPredictionJob::dispatch($lead->uuid);

        return back()->with('success', 'Conversion prediction refresh queued.');
    }
}
