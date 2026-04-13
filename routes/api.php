<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CRM\ErpMatchController;
use App\Http\Controllers\Api\CRM\AttributionController;
use App\Http\Controllers\Api\CRM\CampaignSpendController;
use App\Http\Controllers\Api\CRM\ChatWidgetController;
use App\Http\Controllers\Api\CRM\LandingPageController;
use App\Http\Controllers\Api\CRM\AutomationWorkflowController;
use App\Http\Controllers\Api\CRM\CallCentrePerformanceController;
use App\Http\Controllers\Api\CRM\CallMonitorController;
use App\Http\Controllers\Api\CRM\CallDispositionController;
use App\Http\Controllers\Api\CRM\CallScriptController;
use App\Http\Controllers\Api\CRM\DiallerController;
use App\Http\Controllers\Api\CRM\LeadController;
use App\Http\Controllers\Api\CRM\LeadMergeController;
use App\Http\Controllers\Api\CRM\LeadScoringController;
use App\Http\Controllers\Api\CRM\QuestionnaireController;
use App\Http\Controllers\Api\CRM\TelecallingCampaignController;
use App\Http\Controllers\Api\CRM\WebFormController;
use App\Http\Controllers\Api\CRM\Webhooks\EducationPortalWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\EmailWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\GoogleLeadWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\IvrWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\MetaLeadWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\SmsGatewayWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\TelephonyWebhookController;
use App\Http\Controllers\Api\CRM\Webhooks\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| A2A-CRM API Routes — /api/v1/crm/...
|--------------------------------------------------------------------------
|
| All CRM API routes are versioned under /api/v1/crm/.
| Each route group requires: auth:sanctum + tenancy middleware.
|
*/

Route::prefix('v1/crm')
    ->name('api.v1.crm.')
    ->middleware(['auth:sanctum', 'tenancy'])
    ->group(function (): void {
        // Health-check (used in tests)
        Route::get('health-check', fn () => response()->json(['success' => true, 'data' => ['status' => 'ok'], 'message' => 'A2A-CRM is operational']));

        // BRD: CRM-LC-011 — Lead management endpoints
        Route::apiResource('leads', LeadController::class)
            ->parameters(['leads' => 'lead:uuid']);

        // BRD: CRM-LC-001 — WebForm management endpoints (external consumers only)
        Route::apiResource('forms', WebFormController::class)
            ->parameters(['forms' => 'form:uuid']);
        Route::apiResource('landing-pages', LandingPageController::class)
            ->parameters(['landing-pages' => 'landingPage:uuid']);
        Route::apiResource('chat-widget/leads', ChatWidgetController::class)
            ->only(['index', 'store', 'show'])
            ->parameters(['leads' => 'chatLead:uuid']);
        Route::post('chat-widget/leads/{chatLead:uuid}/reply', [ChatWidgetController::class, 'reply'])
            ->name('chat-widget.leads.reply');
        Route::post('chat-widget/leads/{chatLead:uuid}/ai-reply', [ChatWidgetController::class, 'generateAiReply'])
            ->name('chat-widget.leads.ai-reply');
        Route::patch('chat-widget/leads/{chatLead:uuid}/handoff', [ChatWidgetController::class, 'updateHandoff'])
            ->name('chat-widget.leads.handoff');
        // BRD: CRM-LC-016 — Attribution ledger and touchpoint ingestion
        Route::get('attributions/leads/{lead:uuid}', [AttributionController::class, 'index'])
            ->name('attributions.index');
        Route::post('attributions/leads/{lead:uuid}/touchpoints', [AttributionController::class, 'store'])
            ->name('attributions.store');

        // BRD: CRM-LC-017 — Campaign spend CRUD-lite and CPL report
        Route::get('campaign-spends', [CampaignSpendController::class, 'index'])
            ->name('campaign-spends.index');
        Route::post('campaign-spends', [CampaignSpendController::class, 'store'])
            ->name('campaign-spends.store');
        // BRD: CRM-MA-001 — Automation workflow builder API for external integrations
        Route::apiResource('automation/workflows', AutomationWorkflowController::class)
            ->parameters(['workflows' => 'automationWorkflow:uuid']);
        // BRD: CRM-MA-010 — Automation workflow performance reporting API
        Route::get('automation/workflows-performance', [AutomationWorkflowController::class, 'performanceReport'])
            ->name('automation.workflows.performance');
        // BRD: CRM-TC-007 — Call centre performance dashboard API endpoint
        Route::get('voice/performance', [CallCentrePerformanceController::class, 'performance'])
            ->name('voice.performance');
        // BRD: CRM-LC-009 — QR code PNG download
        Route::get('forms/{form:uuid}/qr', [WebFormController::class, 'qr'])
            ->name('crm.forms.qr');

        // BRD: CRM-LQ-001, CRM-LQ-005, CRM-LQ-007 — Scoring configuration + manual override
        Route::get('scoring/config', [LeadScoringController::class, 'config'])
            ->name('scoring.config');
        Route::put('scoring/config', [LeadScoringController::class, 'updateConfig'])
            ->name('scoring.config.update');
        Route::post('leads/{lead:uuid}/score-override', [LeadScoringController::class, 'override'])
            ->name('leads.score-override');
        Route::get('leads/{lead:uuid}/ai-score', [LeadScoringController::class, 'aiScore'])
            ->name('leads.ai-score');
        Route::post('leads/{lead:uuid}/ai-score/recalculate', [LeadScoringController::class, 'triggerAiRecalculation'])
            ->name('leads.ai-score.recalculate');
        Route::get('leads/{lead:uuid}/churn-risk', [LeadScoringController::class, 'churnRisk'])
            ->name('leads.churn-risk');
        Route::post('leads/{lead:uuid}/churn-risk/recalculate', [LeadScoringController::class, 'triggerChurnRecalculation'])
            ->name('leads.churn-risk.recalculate');
        Route::get('leads/{lead:uuid}/next-best-action', [LeadScoringController::class, 'nextBestAction'])
            ->name('leads.next-best-action');
        Route::post('leads/{lead:uuid}/next-best-action/recalculate', [LeadScoringController::class, 'triggerNbaRecalculation'])
            ->name('leads.next-best-action.recalculate');
        Route::get('leads/{lead:uuid}/ai-drafts', [LeadScoringController::class, 'aiMessageDraft'])
            ->name('leads.ai-drafts');
        Route::post('leads/{lead:uuid}/ai-drafts/generate', [LeadScoringController::class, 'triggerAiMessageDraft'])
            ->name('leads.ai-drafts.generate');
        Route::get('leads/{lead:uuid}/sentiment', [LeadScoringController::class, 'sentiment'])
            ->name('leads.sentiment');
        Route::post('leads/{lead:uuid}/sentiment/recalculate', [LeadScoringController::class, 'triggerSentimentRecalculation'])
            ->name('leads.sentiment.recalculate');
        Route::get('scoring/priority-leads', [LeadScoringController::class, 'priorityLeads'])
            ->name('scoring.priority-leads');
        Route::post('scoring/priority-leads/generate', [LeadScoringController::class, 'triggerPriorityLeadGeneration'])
            ->name('scoring.priority-leads.generate');
        Route::get('scoring/enrolment-forecasts', [LeadScoringController::class, 'enrolmentForecasts'])
            ->name('scoring.enrolment-forecasts');
        Route::post('scoring/enrolment-forecasts/generate', [LeadScoringController::class, 'triggerEnrolmentForecastGeneration'])
            ->name('scoring.enrolment-forecasts.generate');
        Route::get('scoring/anomaly-alerts', [LeadScoringController::class, 'anomalyAlerts'])
            ->name('scoring.anomaly-alerts');
        Route::post('scoring/anomaly-alerts/detect', [LeadScoringController::class, 'triggerAnomalyDetection'])
            ->name('scoring.anomaly-alerts.detect');
        Route::get('scoring/nba-journeys', [LeadScoringController::class, 'nbaJourneys'])
            ->name('scoring.nba-journeys');
        Route::post('scoring/nba-journeys/generate', [LeadScoringController::class, 'triggerNbaJourneyGeneration'])
            ->name('scoring.nba-journeys.generate');
        Route::post('scoring/ai-suggestions/decision', [LeadScoringController::class, 'storeAiSuggestionDecision'])
            ->name('scoring.ai-suggestions.decision');
        Route::get('scoring/ai-usage-logs', [LeadScoringController::class, 'aiUsageLogs'])
            ->name('scoring.ai-usage-logs');

        // BRD: CRM-TC-001 — Dialler session APIs for mobile/ERP integrations
        Route::get('dialler/sessions', [DiallerController::class, 'index'])
            ->name('dialler.sessions.index');
        Route::post('dialler/sessions', [DiallerController::class, 'store'])
            ->name('dialler.sessions.store');
        Route::get('dialler/sessions/{diallerSession:uuid}', [DiallerController::class, 'show'])
            ->name('dialler.sessions.show');
        Route::post('dialler/sessions/{diallerSession:uuid}/stop', [DiallerController::class, 'stop'])
            ->name('dialler.sessions.stop');
        Route::post('dialler/sessions/{diallerSession:uuid}/dispatch-next', [DiallerController::class, 'dispatchNext'])
            ->name('dialler.sessions.dispatch-next');

        // BRD: CRM-TC-002 — Call script CRUD + branch resolution
        Route::apiResource('voice/call-scripts', CallScriptController::class)
            ->parameters(['call-scripts' => 'callScript:uuid']);
        Route::post('voice/call-scripts/{callScript:uuid}/resolve', [CallScriptController::class, 'resolve'])
            ->name('voice.call-scripts.resolve');

        // BRD: CRM-TC-006 — Telecalling campaign management APIs
        Route::get('voice/campaigns', [TelecallingCampaignController::class, 'index'])
            ->name('voice.campaigns.index');
        Route::post('voice/campaigns', [TelecallingCampaignController::class, 'store'])
            ->name('voice.campaigns.store');
        Route::get('voice/campaigns/{telecallingCampaign:uuid}', [TelecallingCampaignController::class, 'show'])
            ->name('voice.campaigns.show');
        Route::put('voice/campaigns/{telecallingCampaign:uuid}', [TelecallingCampaignController::class, 'update'])
            ->name('voice.campaigns.update');
        Route::post('voice/campaigns/{telecallingCampaign:uuid}/launch', [TelecallingCampaignController::class, 'launch'])
            ->name('voice.campaigns.launch');

        // BRD: CRM-TC-005 — Supervisor call monitoring APIs
        Route::get('voice/call-monitor/sessions', [CallMonitorController::class, 'index'])
            ->name('voice.call-monitor.sessions.index');
        Route::post('voice/call-monitor/sessions', [CallMonitorController::class, 'store'])
            ->name('voice.call-monitor.sessions.store');
        Route::post('voice/call-monitor/sessions/{callMonitorLog:uuid}/stop', [CallMonitorController::class, 'stop'])
            ->name('voice.call-monitor.sessions.stop');

        // BRD: CRM-TC-003 — Call disposition configuration APIs
        Route::get('voice/call-dispositions', [CallDispositionController::class, 'index'])
            ->name('voice.call-dispositions.index');
        Route::post('voice/call-dispositions', [CallDispositionController::class, 'store'])
            ->name('voice.call-dispositions.store');
        Route::put('voice/call-dispositions/{callDispositionConfig:uuid}', [CallDispositionController::class, 'update'])
            ->name('voice.call-dispositions.update');

        // BRD: CRM-LQ-009 — Qualification questionnaire CRUD + response capture
        Route::apiResource('scoring/questionnaires', QuestionnaireController::class)
            ->parameters(['questionnaires' => 'questionnaire:uuid']);
        Route::put('scoring/questionnaires/{questionnaire:uuid}/responses/{lead:uuid}', [QuestionnaireController::class, 'upsertResponse'])
            ->name('scoring.questionnaires.responses.upsert')
            ->withoutScopedBindings();

        // -----------------------------------------------------------------------
        // Group G — Duplicate Merge + ERP Lead Match
        // BRD: CRM-LC-019, CRM-LC-020
        // -----------------------------------------------------------------------

        // BRD: CRM-LC-019 — Manual lead merge; returns 202 Accepted (async job)
        Route::post('leads/{lead:uuid}/merge', LeadMergeController::class)
            ->name('leads.merge');
        Route::get('leads/{lead:uuid}/merge-status', [LeadMergeController::class, 'status'])
            ->name('leads.merge-status');

        // BRD: CRM-LC-020 — ERP Student Master match check (trigger + query)
        Route::post('leads/{lead:uuid}/check-erp', ErpMatchController::class)
            ->name('leads.check-erp');
        Route::get('leads/{lead:uuid}/erp-match', [ErpMatchController::class, 'show'])
            ->name('leads.erp-match');
    });

// -----------------------------------------------------------------------
// Webhook routes — external platform → CRM
// No auth:sanctum — verified by VerifyWebhookSignature middleware (HMAC-SHA256)
// Throttled to 60 req/min to prevent abuse
// -----------------------------------------------------------------------
Route::prefix('v1/crm/webhooks')
    ->name('api.crm.webhooks.')
    ->middleware(['throttle:60,1'])
    ->group(function (): void {
        // BRD: CRM-LC-003 — Google Lead Form Extensions webhook
        Route::post('google/{integration}', GoogleLeadWebhookController::class)
            ->middleware('crm.webhook:google')
            ->name('google');

        // BRD: CRM-LC-004 — Meta Lead Ads webhook (GET = challenge, POST = lead event)
        Route::get('meta/{integration}', [MetaLeadWebhookController::class, 'verify'])
            ->name('meta.verify');
        Route::post('meta/{integration}', [MetaLeadWebhookController::class, 'receive'])
            ->middleware('crm.webhook:meta')
            ->name('meta.receive');

        // BRD: CRM-LC-008 — Education portal webhooks (Shiksha, CollegeDekho, Careers360, Collegedunia)
        Route::post('portal/{channel}/{integration}', EducationPortalWebhookController::class)
            ->middleware('crm.webhook:portal')
            ->name('portal');

        // -----------------------------------------------------------------------
        // Group F — Communication Engine webhook receivers
        // No Sanctum auth — verified internally per controller (HMAC / IP allowlist)
        // BRD: CRM-CC-003, CRM-CC-008, CRM-CC-011, CRM-CC-017, CRM-CC-019
        // -----------------------------------------------------------------------

        // F1: Email delivery/open/bounce webhooks (Mailgun, SendGrid, SES)
        Route::post('email/{provider}', EmailWebhookController::class)
            ->name('email');

        // F2: SMS delivery receipt webhooks (MSG91, Textlocal, Kaleyra)
        Route::post('sms/{gateway}', SmsGatewayWebhookController::class)
            ->name('sms');

        // F3: WhatsApp inbound + status updates (Meta Cloud API)
        Route::get('whatsapp', [WhatsAppWebhookController::class, 'verify'])
            ->name('whatsapp.verify');
        Route::post('whatsapp', [WhatsAppWebhookController::class, 'receive'])
            ->name('whatsapp.receive');

        // F4: Telephony call status callbacks (Exotel, Ozonetel, Knowlarity)
        Route::post('telephony/{provider}', TelephonyWebhookController::class)
            ->name('telephony');

        // F4: IVR inbound call → lead auto-creation
        Route::post('ivr/{provider}', IvrWebhookController::class)
            ->name('ivr');
    });
