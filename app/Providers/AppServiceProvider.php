<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\CRM\BulkImportCompletedEvent;
use App\Events\CRM\DigitalLeadImportedEvent;
use App\Events\CRM\LeadTemperatureChangedEvent;
use App\Events\CRM\WebFormSubmittedEvent;
use App\Listeners\CRM\NotifyImportCompleted;
use App\Listeners\CRM\RecalculateScoreOnFormSubmit;
use App\Listeners\CRM\TriggerDuplicateDetectionOnImport;
use App\Listeners\CRM\TriggerScoringWorkflowListener;
use App\Services\CRM\TenantManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // BRD: Multi-tenancy — TenantManager resolves institution_id for every request
        $this->app->singleton(TenantManager::class, fn () => new TenantManager);
    }

    public function boot(): void
    {
        // BRD: CRM-LC-003, CRM-LC-004, CRM-LC-008 — Trigger dedup after every digital channel import
        Event::listen(DigitalLeadImportedEvent::class, TriggerDuplicateDetectionOnImport::class);

        // BRD: CRM-LC-012 — Notify initiating user when bulk import batch completes
        Event::listen(BulkImportCompletedEvent::class, NotifyImportCompleted::class);

        // BRD: CRM-LQ-006 — Trigger automated workflow (HOT alert / COLD nurture) on temperature change
        Event::listen(LeadTemperatureChangedEvent::class, TriggerScoringWorkflowListener::class);

        // BRD: CRM-LQ-004 — Recalculate score on web form submission (engagement signal)
        Event::listen(WebFormSubmittedEvent::class, RecalculateScoreOnFormSubmit::class);
    }
}
