<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\CRM\BulkImportCompletedEvent;
use App\Events\CRM\Communication\CallLoggedEvent;
use App\Events\CRM\Communication\EmailBouncedEvent;
use App\Events\CRM\Communication\EmailSentEvent;
use App\Events\CRM\Communication\EmailUnsubscribedEvent;
use App\Events\CRM\Communication\IvrLeadCreatedEvent;
use App\Events\CRM\Communication\MissedCallReceivedEvent;
use App\Events\CRM\Communication\WhatsAppLeadCreatedEvent;
use App\Events\CRM\Communication\WhatsAppMessageReceivedEvent;
use App\Events\CRM\CounsellingSessionBookedEvent;
use App\Events\CRM\CounsellingSessionCancelledEvent;
use App\Events\CRM\CounsellingSessionCompletedEvent;
use App\Events\CRM\DigitalLeadImportedEvent;
use App\Events\CRM\ErpStudentMatchedEvent;
use App\Events\CRM\LeadAssignedEvent;
use App\Events\CRM\LeadCreatedEvent;
use App\Events\CRM\LeadStatusChangedEvent;
use App\Events\CRM\LeadTemperatureChangedEvent;
use App\Events\CRM\LeadsMergedEvent;
use App\Events\CRM\WebFormSubmittedEvent;
use App\Listeners\CRM\HandleEmailBounce;
use App\Listeners\CRM\HandleLeadUnsubscribe;
use App\Listeners\CRM\CaptureLeadAttributionOnCreate;
use App\Listeners\CRM\LogCallToActivityTimeline;
use App\Listeners\CRM\LogEmailSentToActivity;
use App\Listeners\CRM\LogWhatsAppToActivityTimeline;
use App\Listeners\CRM\NotifyAssignedCounsellorOnInbound;
use App\Listeners\CRM\NotifyCounsellorOnMissedCall;
use App\Listeners\CRM\LogAssignmentActivity;
use App\Listeners\CRM\LogLeadCreatedActivity;
use App\Listeners\CRM\LogSessionBookedActivity;
use App\Listeners\CRM\LogSessionCancelledActivity;
use App\Listeners\CRM\LogSessionCompletedActivity;
use App\Listeners\CRM\LogStatusChangeActivity;
use App\Listeners\CRM\NotifyImportCompleted;
use App\Listeners\CRM\RecalculateScoreOnFormSubmit;
use App\Listeners\CRM\TriggerDuplicateDetectionOnImport;
use App\Listeners\CRM\LogErpMatchActivity;
use App\Listeners\CRM\LogMergeActivity;
use App\Listeners\CRM\TriggerScoringWorkflowListener;
use App\Listeners\CRM\TriggerStatusWorkflowListener;
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

        // BRD: CRM-EC-004 — Log activity entry when a lead is created
        Event::listen(LeadCreatedEvent::class, LogLeadCreatedActivity::class);
        // BRD: CRM-LC-016 — Record first attribution touchpoint for each new lead
        Event::listen(LeadCreatedEvent::class, CaptureLeadAttributionOnCreate::class);

        // BRD: CRM-EC-014 — Log activity entry on every status transition
        Event::listen(LeadStatusChangedEvent::class, LogStatusChangeActivity::class);

        // BRD: CRM-EC-012 — Trigger configured workflow automation on status change
        Event::listen(LeadStatusChangedEvent::class, TriggerStatusWorkflowListener::class);

        // BRD: CRM-EC-004 — Log ASSIGNMENT activity on lead (re)assignment
        Event::listen(LeadAssignedEvent::class, LogAssignmentActivity::class);

        // BRD: CRM-EC-015 — Log activity entries for session lifecycle events
        Event::listen(CounsellingSessionBookedEvent::class, LogSessionBookedActivity::class);
        Event::listen(CounsellingSessionCompletedEvent::class, LogSessionCompletedActivity::class);
        Event::listen(CounsellingSessionCancelledEvent::class, LogSessionCancelledActivity::class);

        // -----------------------------------------------------------------------
        // Group F — Communication Engine events (BRD: CRM-CC-001 to CRM-CC-025)
        // -----------------------------------------------------------------------

        // F1: Email delivery events
        // BRD: CRM-CC-002 — Log sent email to lead activity timeline
        Event::listen(EmailSentEvent::class, LogEmailSentToActivity::class);
        // BRD: CRM-CC-003 — Handle bounce: increment counter + flag lead
        Event::listen(EmailBouncedEvent::class, HandleEmailBounce::class);
        // BRD: CRM-CC-005, DPDP — Enforce unsubscribe and audit log entry
        Event::listen(EmailUnsubscribedEvent::class, HandleLeadUnsubscribe::class);

        // F3: WhatsApp events
        // BRD: CRM-CC-012 — Log inbound WhatsApp to lead activity timeline
        Event::listen(WhatsAppMessageReceivedEvent::class, LogWhatsAppToActivityTimeline::class);
        // BRD: CRM-CC-023 — Notify assigned counsellor on inbound message
        Event::listen(WhatsAppMessageReceivedEvent::class, NotifyAssignedCounsellorOnInbound::class);
        // BRD: CRM-LC-007 — WhatsApp auto-created lead: score trigger (handled by scoring service)
        Event::listen(WhatsAppLeadCreatedEvent::class, TriggerScoringWorkflowListener::class);

        // F4: Voice/IVR events
        // BRD: CRM-CC-017 — Log call to activity timeline
        Event::listen(CallLoggedEvent::class, LogCallToActivityTimeline::class);
        // BRD: CRM-CC-018 — Notify counsellor on missed call
        Event::listen(MissedCallReceivedEvent::class, NotifyCounsellorOnMissedCall::class);
        // BRD: CRM-LC-010 — IVR auto-created lead: trigger scoring workflow
        Event::listen(IvrLeadCreatedEvent::class, TriggerScoringWorkflowListener::class);

        // -----------------------------------------------------------------------
        // Group G — Duplicate Merge + ERP Match (BRD: CRM-LC-019, CRM-LC-020)
        // -----------------------------------------------------------------------

        // BRD: CRM-LC-019 — Log MERGE activity on both primary and secondary leads after merge
        Event::listen(LeadsMergedEvent::class, LogMergeActivity::class);

        // BRD: CRM-LC-020 — Log ERP student/alumni match to lead activity timeline
        Event::listen(ErpStudentMatchedEvent::class, LogErpMatchActivity::class);
    }
}
