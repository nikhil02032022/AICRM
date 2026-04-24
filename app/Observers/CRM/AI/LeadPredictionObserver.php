<?php

declare(strict_types=1);

namespace App\Observers\CRM\AI;

use App\Enums\CRM\MessageDirection;
use App\Events\CRM\CommunicationLogCreatedEvent;
use App\Events\CRM\CounsellingSessionBookedEvent;
use App\Events\CRM\LeadStatusChangedEvent;
use App\Events\CRM\ScoreChangedEvent;
use App\Jobs\CRM\AI\RefreshConversionPredictionJob;
use Illuminate\Events\Dispatcher;

// BRD: CRM-AI-001 — Event subscriber that triggers conversion prediction refresh on key lead lifecycle events
final class LeadPredictionObserver
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(LeadStatusChangedEvent::class,       self::class.'@onLeadStatusChanged');
        $events->listen(CommunicationLogCreatedEvent::class,  self::class.'@onCommunicationLogCreated');
        $events->listen(ScoreChangedEvent::class,             self::class.'@onScoreChanged');
        $events->listen(CounsellingSessionBookedEvent::class, self::class.'@onCounsellingSessionBooked');
    }

    public function onLeadStatusChanged(LeadStatusChangedEvent $event): void
    {
        RefreshConversionPredictionJob::dispatch($event->lead->uuid);
    }

    public function onCommunicationLogCreated(CommunicationLogCreatedEvent $event): void
    {
        // Only inbound messages update prediction — outbound activity is counsellor-initiated
        if ($event->log->direction !== MessageDirection::INBOUND) {
            return;
        }

        if ($event->log->lead_id === null) {
            return;
        }

        $leadUuid = $event->log->lead?->uuid;

        if ($leadUuid === null) {
            return;
        }

        RefreshConversionPredictionJob::dispatch($leadUuid);
    }

    public function onScoreChanged(ScoreChangedEvent $event): void
    {
        RefreshConversionPredictionJob::dispatch($event->lead->uuid);
    }

    public function onCounsellingSessionBooked(CounsellingSessionBookedEvent $event): void
    {
        $leadUuid = $event->session->lead?->uuid;

        if ($leadUuid === null) {
            return;
        }

        RefreshConversionPredictionJob::dispatch($leadUuid);
    }
}
