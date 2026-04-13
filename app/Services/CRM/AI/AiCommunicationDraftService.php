<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Models\CRM\AiMessageDraft;
use App\Models\CRM\Lead;
use Illuminate\Support\Str;

// BRD: CRM-AI-003 — AI-assisted communication draft generator using lead context and recommendation signals
final class AiCommunicationDraftService
{
    public function generateAndPersist(Lead $lead, string $channel): AiMessageDraft
    {
        $nba = $lead->nbaRecommendations()->latest('generated_at')->first();
        $churn = $lead->churnFlags()->latest('flagged_at')->first();

        $name = trim($lead->first_name.' '.$lead->last_name);
        $programme = $lead->programmeInterests()->first()?->name ?? 'your selected programme';
        $nbaText = $nba?->recommended_action ? str_replace('_', ' ', $nba->recommended_action) : 'a personalised follow-up';

        $subject = null;
        $draftText = '';

        if ($channel === 'email') {
            $subject = 'Next steps for '.$programme.' at Demo University';
            $draftText = "Dear {$name},\n\nThank you for your interest in {$programme}. Based on your current admission profile, we recommend {$nbaText} as the next step.\n\nIf you would like, we can also schedule a counsellor call to help with fees, eligibility, and timelines.\n\nRegards,\nAdmissions Team";
        }

        if ($channel === 'whatsapp') {
            $draftText = "Hi {$name}, thanks for connecting with us for {$programme}. Based on your profile, our next recommended step is {$nbaText}. Reply here and we can help you complete this quickly.";
        }

        return AiMessageDraft::withoutGlobalScopes()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $lead->institution_id,
            'campus_id' => $lead->campus_id,
            'lead_id' => $lead->id,
            'channel' => $channel,
            'subject' => $subject,
            'draft_text' => $draftText,
            'context' => [
                'lead_score' => (int) $lead->lead_score,
                'temperature' => $lead->temperature?->value,
                'nba_action' => $nba?->recommended_action,
                'churn_risk_score' => $churn?->risk_score,
            ],
            'metadata' => [
                'source' => 'rule_assisted_draft',
            ],
            'model_version' => 'a2a-draft-rules-v1',
            'generated_at' => now(),
        ]);
    }
}
