<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Models\CRM\Lead;
use App\Services\CRM\AI\AiSuggestionDecisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

// BRD: CRM-AI-001, CRM-AI-011 — Records counsellor accept/reject decision on a conversion probability prediction
final class AiPredictionFeedbackController extends Controller
{
    public function __construct(
        private readonly AiSuggestionDecisionService $decisionService,
    ) {}

    /**
     * Store a counsellor's accept or reject decision for a conversion prediction.
     * BRD: CRM-AI-001, CRM-AI-011
     */
    public function store(Request $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('ai.prediction.feedback', $lead);

        $validated = $request->validate([
            'suggestion_uuid' => ['required', 'uuid'],
            'decision'        => ['required', Rule::in(['accepted', 'rejected'])],
            'notes'           => ['nullable', 'string', 'max:500'],
        ]);

        $this->decisionService->record(
            institutionId: (int) $request->user()->institution_id,
            actorId: (int) $request->user()->id,
            validated: [
                'lead_uuid'       => $lead->uuid,
                'suggestion_type' => 'conversion_prediction',
                'suggestion_uuid' => $validated['suggestion_uuid'],
                'decision'        => $validated['decision'],
                'notes'           => $validated['notes'] ?? null,
            ],
        );

        $label = $validated['decision'] === 'accepted' ? 'accepted' : 'rejected';

        return back()->with('success', "Prediction {$label} and recorded.");
    }
}
