<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Enums\CRM\LeadTemperature;
use App\Models\CRM\Lead;
use App\Models\CRM\LeadNbaRecommendation;
use Illuminate\Support\Str;

// BRD: CRM-AI-002 — Rule-assisted NBA recommendation service for counsellor next action guidance
final class NbaRecommendationService
{
    public function generateAndPersist(Lead $lead): LeadNbaRecommendation
    {
        $latestChurn = $lead->churnFlags()->latest('flagged_at')->first();

        $recommendedAction = 'send_whatsapp';
        $channels = ['whatsapp'];
        $confidence = 62;
        $reasoning = 'Lead is active in pipeline; a personalised WhatsApp follow-up is recommended to sustain engagement.';

        if ($latestChurn !== null && (int) $latestChurn->risk_score >= 70) {
            $recommendedAction = 'call_within_24h';
            $channels = ['voice'];
            $confidence = 85;
            $reasoning = 'High churn risk detected. Immediate counsellor call within 24 hours is the highest-impact action.';
        } elseif ($lead->temperature === LeadTemperature::HOT) {
            $recommendedAction = 'invite_to_campus_visit';
            $channels = ['whatsapp', 'email'];
            $confidence = 78;
            $reasoning = 'Lead temperature is HOT. Converting intent through a campus visit or counselling event invitation is recommended.';
        } elseif (! $lead->questionnaireResponses()->exists()) {
            $recommendedAction = 'complete_qualification_questionnaire';
            $channels = ['voice', 'whatsapp'];
            $confidence = 72;
            $reasoning = 'Qualification data is incomplete. Prioritise questionnaire completion to improve scoring confidence and targeting.';
        }

        return LeadNbaRecommendation::withoutGlobalScopes()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $lead->institution_id,
            'campus_id' => $lead->campus_id,
            'lead_id' => $lead->id,
            'recommended_action' => $recommendedAction,
            'reasoning' => $reasoning,
            'confidence_score' => $confidence,
            'channels' => $channels,
            'metadata' => [
                'lead_score' => (int) $lead->lead_score,
                'temperature' => $lead->temperature?->value,
                'churn_risk_score' => $latestChurn?->risk_score,
                'questionnaire_completed' => $lead->questionnaireResponses()->exists(),
            ],
            'model_version' => 'a2a-nba-rules-v1',
            'generated_at' => now(),
        ]);
    }
}
