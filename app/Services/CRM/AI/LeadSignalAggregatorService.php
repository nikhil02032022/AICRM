<?php

declare(strict_types=1);

namespace App\Services\CRM\AI;

use App\Enums\CRM\Documents\DocumentStatus;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\MessageDirection;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\CounsellingSession;
use App\Models\CRM\Documents\ApplicationDocument;
use App\Models\CRM\Lead;
use App\Models\CRM\LeadAttribution;
use App\Models\CRM\Payments\PaymentTransaction;
use Carbon\Carbon;

// BRD: CRM-AI-001 — Aggregates per-lead behavioural signals for Claude API conversion prediction
final class LeadSignalAggregatorService
{
    private const SIGNAL_WINDOW_DAYS = 90;

    /** @return array<string, mixed> */
    public function aggregate(Lead $lead): array
    {
        $windowStart = Carbon::now()->subDays(self::SIGNAL_WINDOW_DAYS);

        return [
            'source_quality_score'     => $this->sourceQualityScore($lead),
            'days_since_enquiry'        => (int) Carbon::parse($lead->created_at)->diffInDays(now()),
            'days_to_first_contact'     => $this->daysToFirstContact($lead, $windowStart),
            'inbound_message_count'     => $this->inboundMessageCount($lead, $windowStart),
            'document_completion_pct'   => $this->documentCompletionPct($lead),
            'payment_attempts'          => $this->paymentAttempts($lead),
            'counselling_session_count' => $this->counsellingSessionCount($lead, $windowStart),
            'programme_interest_count'  => $lead->programmeInterests()->count(),
            'questionnaire_completed'   => $lead->questionnaireResponses()->exists(),
            'consent_given'             => (bool) $lead->consent_given,
            'temperature'               => $lead->temperature?->value ?? 'unknown',
            'current_status'            => $lead->status?->value ?? 'unknown',
            'lead_score'                => (int) $lead->lead_score,
        ];
    }

    private function sourceQualityScore(Lead $lead): float
    {
        // Quality map based on historical conversion rates by source type
        $qualityMap = [
            LeadSource::REFERRAL->value         => 0.90,
            LeadSource::WALK_IN->value          => 0.85,
            LeadSource::AGENT->value            => 0.80,
            LeadSource::EVENT->value            => 0.75,
            LeadSource::WEBSITE_ORGANIC->value  => 0.70,
            LeadSource::GOOGLE_ADS->value       => 0.65,
            LeadSource::FACEBOOK->value         => 0.60,
            LeadSource::INSTAGRAM->value        => 0.58,
            LeadSource::WHATSAPP->value         => 0.55,
            LeadSource::EDUCATION_PORTAL->value => 0.55,
            LeadSource::LIVE_CHAT->value        => 0.50,
            LeadSource::IVR->value              => 0.50,
            LeadSource::QR_CODE->value          => 0.45,
            LeadSource::CSV_IMPORT->value       => 0.40,
            LeadSource::API->value              => 0.40,
        ];

        return $qualityMap[$lead->source?->value ?? ''] ?? 0.50;
    }

    private function daysToFirstContact(Lead $lead, Carbon $windowStart): ?float
    {
        $firstOutbound = CommunicationLog::withoutGlobalScopes()
            ->where('lead_id', $lead->id)
            ->where('direction', MessageDirection::OUTBOUND->value)
            ->where('created_at', '>=', $windowStart)
            ->orderBy('created_at')
            ->value('created_at');

        if ($firstOutbound === null) {
            return null;
        }

        return round(Carbon::parse($lead->created_at)->floatDiffInDays(Carbon::parse($firstOutbound)), 2);
    }

    private function inboundMessageCount(Lead $lead, Carbon $windowStart): int
    {
        return CommunicationLog::withoutGlobalScopes()
            ->where('lead_id', $lead->id)
            ->where('direction', MessageDirection::INBOUND->value)
            ->where('created_at', '>=', $windowStart)
            ->count();
    }

    private function documentCompletionPct(Lead $lead): float
    {
        $total = ApplicationDocument::withoutGlobalScopes()
            ->where('lead_uuid', $lead->uuid)
            ->count();

        if ($total === 0) {
            return 0.0;
        }

        $verified = ApplicationDocument::withoutGlobalScopes()
            ->where('lead_uuid', $lead->uuid)
            ->where('status', DocumentStatus::VERIFIED->value)
            ->count();

        return round($verified / $total, 4);
    }

    private function paymentAttempts(Lead $lead): int
    {
        return PaymentTransaction::withoutGlobalScopes()
            ->where('lead_uuid', $lead->uuid)
            ->count();
    }

    private function counsellingSessionCount(Lead $lead, Carbon $windowStart): int
    {
        return CounsellingSession::withoutGlobalScopes()
            ->where('lead_id', $lead->id)
            ->where('created_at', '>=', $windowStart)
            ->count();
    }
}
