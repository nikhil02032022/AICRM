<?php

declare(strict_types=1);

namespace App\Listeners\CRM;

use App\Events\CRM\AiSuggestionDecisionRecordedEvent;
use App\Events\CRM\AnomalyDetectedEvent;
use App\Events\CRM\ForecastGeneratedEvent;
use App\Events\CRM\LeadAiMessageDraftedEvent;
use App\Events\CRM\LeadAiScoreCalculatedEvent;
use App\Events\CRM\LeadNbaRecommendedEvent;
use App\Events\CRM\LeadSentimentFlaggedEvent;
use App\Events\CRM\NbaJourneySuggestedEvent;
use App\Services\CRM\AI\AiUsageLoggingService;

// BRD: CRM-AI-012 — Listener that converts AI domain events into immutable usage logs
final class RecordAiUsageLogFromEvent
{
    public function __construct(
        private readonly AiUsageLoggingService $aiUsageLoggingService,
    ) {}

    public function handle(object $event): void
    {
        match (true) {
            $event instanceof LeadAiScoreCalculatedEvent => $this->recordLeadScore($event),
            $event instanceof LeadNbaRecommendedEvent => $this->recordNba($event),
            $event instanceof LeadAiMessageDraftedEvent => $this->recordDraft($event),
            $event instanceof LeadSentimentFlaggedEvent => $this->recordSentiment($event),
            $event instanceof AnomalyDetectedEvent => $this->recordAnomaly($event),
            $event instanceof ForecastGeneratedEvent => $this->recordForecast($event),
            $event instanceof NbaJourneySuggestedEvent => $this->recordJourney($event),
            $event instanceof AiSuggestionDecisionRecordedEvent => $this->recordDecision($event),
            default => null,
        };
    }

    private function recordLeadScore(LeadAiScoreCalculatedEvent $event): void
    {
        $snapshot = $event->aiLeadScore;

        $this->aiUsageLoggingService->log(
            institutionId: (int) $snapshot->institution_id,
            campusId: $snapshot->campus_id !== null ? (int) $snapshot->campus_id : null,
            leadId: (int) $snapshot->lead_id,
            actorId: null,
            featureKey: 'ai_lead_scoring',
            action: 'generated',
            eventName: LeadAiScoreCalculatedEvent::class,
            referenceUuid: (string) $snapshot->uuid,
            context: ['model_version' => $snapshot->model_version],
        );
    }

    private function recordNba(LeadNbaRecommendedEvent $event): void
    {
        $recommendation = $event->recommendation;

        $this->aiUsageLoggingService->log(
            institutionId: (int) $recommendation->institution_id,
            campusId: $recommendation->campus_id !== null ? (int) $recommendation->campus_id : null,
            leadId: (int) $recommendation->lead_id,
            actorId: null,
            featureKey: 'next_best_action',
            action: 'recommended',
            eventName: LeadNbaRecommendedEvent::class,
            referenceUuid: (string) $recommendation->uuid,
            context: ['confidence_score' => (int) $recommendation->confidence_score],
        );
    }

    private function recordDraft(LeadAiMessageDraftedEvent $event): void
    {
        $draft = $event->draft;

        $this->aiUsageLoggingService->log(
            institutionId: (int) $draft->institution_id,
            campusId: $draft->campus_id !== null ? (int) $draft->campus_id : null,
            leadId: (int) $draft->lead_id,
            actorId: null,
            featureKey: 'message_draft',
            action: 'generated',
            eventName: LeadAiMessageDraftedEvent::class,
            referenceUuid: (string) $draft->uuid,
            context: ['channel' => $draft->channel],
        );
    }

    private function recordSentiment(LeadSentimentFlaggedEvent $event): void
    {
        $flag = $event->sentimentFlag;

        $this->aiUsageLoggingService->log(
            institutionId: (int) $flag->institution_id,
            campusId: $flag->campus_id !== null ? (int) $flag->campus_id : null,
            leadId: (int) $flag->lead_id,
            actorId: null,
            featureKey: 'sentiment_analysis',
            action: 'flagged',
            eventName: LeadSentimentFlaggedEvent::class,
            referenceUuid: (string) $flag->uuid,
            context: ['sentiment_label' => $flag->sentiment_label?->value],
        );
    }

    private function recordAnomaly(AnomalyDetectedEvent $event): void
    {
        $alert = $event->anomalyAlert;

        $this->aiUsageLoggingService->log(
            institutionId: (int) $alert->institution_id,
            campusId: $alert->campus_id !== null ? (int) $alert->campus_id : null,
            leadId: null,
            actorId: null,
            featureKey: 'anomaly_detection',
            action: 'alerted',
            eventName: AnomalyDetectedEvent::class,
            referenceUuid: (string) $alert->uuid,
            context: ['metric_name' => $alert->metric_name, 'severity' => $alert->severity],
        );
    }

    private function recordForecast(ForecastGeneratedEvent $event): void
    {
        $this->aiUsageLoggingService->log(
            institutionId: $event->institutionId,
            campusId: null,
            leadId: null,
            actorId: null,
            featureKey: 'enrolment_forecast',
            action: 'generated',
            eventName: ForecastGeneratedEvent::class,
            referenceUuid: null,
            context: [
                'generated_for_month' => $event->generatedForMonth,
                'records_generated' => $event->recordsGenerated,
            ],
        );
    }

    private function recordJourney(NbaJourneySuggestedEvent $event): void
    {
        $journey = $event->journey;

        $this->aiUsageLoggingService->log(
            institutionId: (int) $journey->institution_id,
            campusId: $journey->campus_id !== null ? (int) $journey->campus_id : null,
            leadId: null,
            actorId: null,
            featureKey: 'nurture_journey',
            action: 'suggested',
            eventName: NbaJourneySuggestedEvent::class,
            referenceUuid: (string) $journey->uuid,
            context: ['segment_key' => $journey->segment_key],
        );
    }

    private function recordDecision(AiSuggestionDecisionRecordedEvent $event): void
    {
        $decision = $event->decision;

        $this->aiUsageLoggingService->log(
            institutionId: (int) $decision->institution_id,
            campusId: $decision->campus_id !== null ? (int) $decision->campus_id : null,
            leadId: $decision->lead_id !== null ? (int) $decision->lead_id : null,
            actorId: (int) $decision->acted_by,
            featureKey: 'human_decision',
            action: (string) $decision->decision,
            eventName: AiSuggestionDecisionRecordedEvent::class,
            referenceUuid: (string) $decision->uuid,
            context: ['suggestion_type' => $decision->suggestion_type, 'suggestion_uuid' => $decision->suggestion_uuid],
        );
    }
}
