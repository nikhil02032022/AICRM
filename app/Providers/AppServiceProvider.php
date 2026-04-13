<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\CRM\BulkImportCompletedEvent;
use App\Events\CRM\AiSuggestionDecisionRecordedEvent;
use App\Events\CRM\AnomalyDetectedEvent;
use App\Events\CRM\ForecastGeneratedEvent;
use App\Events\CRM\Communication\CallLoggedEvent;
use App\Events\CRM\Communication\EmailBouncedEvent;
use App\Events\CRM\Communication\EmailLinkClickedEvent;
use App\Events\CRM\Communication\EmailOpenedEvent;
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
use App\Events\CRM\LeadAiMessageDraftedEvent;
use App\Events\CRM\LeadAiScoreCalculatedEvent;
use App\Events\CRM\LeadNbaRecommendedEvent;
use App\Events\CRM\LeadSentimentFlaggedEvent;
use App\Events\CRM\LeadCreatedEvent;
use App\Events\CRM\LeadStatusChangedEvent;
use App\Events\CRM\LeadTemperatureChangedEvent;
use App\Events\CRM\LeadsMergedEvent;
use App\Events\CRM\NbaJourneySuggestedEvent;
use App\Events\CRM\WebFormSubmittedEvent;
use App\Listeners\CRM\HandleEmailBounce;
use App\Listeners\CRM\HandleLeadUnsubscribe;
use App\Listeners\CRM\EvaluateAutomationOnEmailLinkClicked;
use App\Listeners\CRM\EvaluateAutomationOnEmailOpened;
use App\Listeners\CRM\EvaluateAutomationOnLeadCreated;
use App\Listeners\CRM\EvaluateAutomationOnLeadStatusChanged;
use App\Listeners\CRM\EvaluateAutomationOnLeadTemperatureChanged;
use App\Listeners\CRM\EvaluateReEngagementOnLeadTemperatureChanged;
use App\Listeners\CRM\EvaluateAutomationOnWebFormSubmitted;
use App\Listeners\CRM\CaptureLeadAttributionOnCreate;
use App\Listeners\CRM\ContinueDiallerOnCallLogged;
use App\Listeners\CRM\LogCallToActivityTimeline;
use App\Listeners\CRM\LogEmailSentToActivity;
use App\Listeners\CRM\LogWhatsAppToActivityTimeline;
use App\Listeners\CRM\NotifyAssignedCounsellorOnInbound;
use App\Listeners\CRM\NotifyCounsellorOnMissedCall;
use App\Listeners\CRM\LogAssignmentActivity;
use App\Listeners\CRM\RecordAiUsageLogFromEvent;
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
        // BRD: CRM-MA-002 — Evaluate form_submitted automation triggers
        Event::listen(WebFormSubmittedEvent::class, EvaluateAutomationOnWebFormSubmitted::class);

        // BRD: CRM-EC-004 — Log activity entry when a lead is created
        Event::listen(LeadCreatedEvent::class, LogLeadCreatedActivity::class);
        // BRD: CRM-LC-016 — Record first attribution touchpoint for each new lead
        Event::listen(LeadCreatedEvent::class, CaptureLeadAttributionOnCreate::class);
        // BRD: CRM-MA-002 — Evaluate lead_created automation triggers
        Event::listen(LeadCreatedEvent::class, EvaluateAutomationOnLeadCreated::class);

        // BRD: CRM-EC-014 — Log activity entry on every status transition
        Event::listen(LeadStatusChangedEvent::class, LogStatusChangeActivity::class);

        // BRD: CRM-EC-012 — Trigger configured workflow automation on status change
        Event::listen(LeadStatusChangedEvent::class, TriggerStatusWorkflowListener::class);
        // BRD: CRM-MA-002 — Evaluate status_changed automation triggers
        Event::listen(LeadStatusChangedEvent::class, EvaluateAutomationOnLeadStatusChanged::class);

        // BRD: CRM-MA-002 — Evaluate lead_score_changed automation triggers
        Event::listen(LeadTemperatureChangedEvent::class, EvaluateAutomationOnLeadTemperatureChanged::class);
        // BRD: CRM-MA-007 — Evaluate re-engagement workflows for cold lead temperature changes
        Event::listen(LeadTemperatureChangedEvent::class, EvaluateReEngagementOnLeadTemperatureChanged::class);

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
        // BRD: CRM-MA-002 — Evaluate email_opened automation triggers
        Event::listen(EmailOpenedEvent::class, EvaluateAutomationOnEmailOpened::class);
        // BRD: CRM-MA-002 — Evaluate link_clicked automation triggers
        Event::listen(EmailLinkClickedEvent::class, EvaluateAutomationOnEmailLinkClicked::class);

        // F3: WhatsApp events
        // BRD: CRM-CC-012 — Log inbound WhatsApp to lead activity timeline
        Event::listen(WhatsAppMessageReceivedEvent::class, LogWhatsAppToActivityTimeline::class);
        // BRD: CRM-CC-023 — Notify assigned counsellor on inbound message
        Event::listen(WhatsAppMessageReceivedEvent::class, NotifyAssignedCounsellorOnInbound::class);
        // BRD: CRM-LC-007 — WhatsApp auto-created lead: score trigger (handled by scoring service)
        Event::listen(WhatsAppLeadCreatedEvent::class, TriggerScoringWorkflowListener::class);

        // F4: Voice/IVR events
        // BRD: CRM-TC-001 — Continue dialler queue after current call finishes.
        Event::listen(CallLoggedEvent::class, ContinueDiallerOnCallLogged::class);

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

        // BRD: CRM-AI-012 — Persist immutable usage logs for all AI generation/decision events.
        Event::listen(LeadAiScoreCalculatedEvent::class, RecordAiUsageLogFromEvent::class);
        Event::listen(LeadNbaRecommendedEvent::class, RecordAiUsageLogFromEvent::class);
        Event::listen(LeadAiMessageDraftedEvent::class, RecordAiUsageLogFromEvent::class);
        Event::listen(LeadSentimentFlaggedEvent::class, RecordAiUsageLogFromEvent::class);
        Event::listen(ForecastGeneratedEvent::class, RecordAiUsageLogFromEvent::class);
        Event::listen(AnomalyDetectedEvent::class, RecordAiUsageLogFromEvent::class);
        Event::listen(NbaJourneySuggestedEvent::class, RecordAiUsageLogFromEvent::class);
        Event::listen(AiSuggestionDecisionRecordedEvent::class, RecordAiUsageLogFromEvent::class);
    }
}
