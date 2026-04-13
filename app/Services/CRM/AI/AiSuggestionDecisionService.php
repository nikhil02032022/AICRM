<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Events\CRM\AiSuggestionDecisionRecordedEvent;
use App\Models\CRM\AiSuggestionDecision;
use App\Models\CRM\Lead;
use Illuminate\Support\Str;

// BRD: CRM-AI-011 — Service that records explicit human decision over AI suggestions
final class AiSuggestionDecisionService
{
    /** @param array<string, mixed> $validated */
    public function record(int $institutionId, int $actorId, array $validated): AiSuggestionDecision
    {
        $leadId = null;

        if (! empty($validated['lead_uuid'])) {
            $lead = Lead::withoutGlobalScopes()
                ->where('institution_id', $institutionId)
                ->where('uuid', (string) $validated['lead_uuid'])
                ->first();

            $leadId = $lead?->id;
        }

        $decision = AiSuggestionDecision::withoutGlobalScopes()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $institutionId,
            'lead_id' => $leadId,
            'suggestion_type' => (string) $validated['suggestion_type'],
            'suggestion_uuid' => isset($validated['suggestion_uuid']) ? (string) $validated['suggestion_uuid'] : null,
            'decision' => (string) $validated['decision'],
            'edited_content' => isset($validated['edited_content']) ? (string) $validated['edited_content'] : null,
            'notes' => isset($validated['notes']) ? (string) $validated['notes'] : null,
            'acted_by' => $actorId,
            'acted_at' => now(),
        ]);

        AiSuggestionDecisionRecordedEvent::dispatch($decision);

        return $decision;
    }
}
