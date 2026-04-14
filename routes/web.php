<?php

declare(strict_types=1);

use App\Http\Controllers\Public\PublicFormController;
use App\Http\Controllers\Public\PublicLandingPageController;
use App\Http\Controllers\Public\PublicChatWidgetController;
use App\Http\Controllers\Public\PublicKioskController;
use App\Http\Controllers\Web\CRM\AttributionWebController;
use App\Http\Controllers\Web\CRM\CustomFieldWebController;
use App\Http\Controllers\Web\CRM\CustomReportWebController;
use App\Http\Controllers\Web\CRM\ReportSchedulerWebController;
use App\Http\Controllers\Web\CRM\SystemHealthWebController;
use App\Http\Controllers\Web\CRM\WorkflowTemplateWebController;
use App\Http\Controllers\Web\CRM\CallLogWebController;
use App\Http\Controllers\Web\CRM\CallDispositionWebController;
use App\Http\Controllers\Web\CRM\CallMonitorWebController;
use App\Http\Controllers\Web\CRM\CallScriptWebController;
use App\Http\Controllers\Web\CRM\TelecallingCampaignWebController;
use App\Http\Controllers\Web\CRM\CallCentrePerformanceWebController;
use App\Http\Controllers\Web\CRM\ChatWidgetWebController;
use App\Http\Controllers\Web\CRM\CostTrackingWebController;
use App\Http\Controllers\Web\CRM\AutomationWorkflowWebController;
use App\Http\Controllers\Web\CRM\ErpMatchWebController;
use App\Http\Controllers\Web\CRM\DiallerWebController;
use App\Http\Controllers\Web\CRM\DncWebController;
use App\Http\Controllers\Web\CRM\LandingPageWebController;
use App\Http\Controllers\Web\CRM\KioskWebController;
use App\Http\Controllers\Web\CRM\LeadMergeWebController;
use App\Http\Controllers\Web\CRM\QuestionnaireWebController;
use App\Http\Controllers\Web\CRM\CommunicationTemplateWebController;
use App\Http\Controllers\Web\CRM\CounsellingWebController;
use App\Http\Controllers\Web\CRM\DltTemplateWebController;
use App\Http\Controllers\Web\CRM\EmailCampaignWebController;
use App\Http\Controllers\Web\CRM\IntegrationWebController;
use App\Http\Controllers\Web\CRM\IvrConfigWebController;
use App\Http\Controllers\Web\CRM\LeadImportWebController;
use App\Http\Controllers\Web\CRM\LeadScoringWebController;
use App\Http\Controllers\Web\CRM\LeadWebController;
use App\Http\Controllers\Web\CRM\PublicBookingController;
use App\Http\Controllers\Web\CRM\SenderDomainWebController;
use App\Http\Controllers\Web\CRM\SessionWebController;
use App\Http\Controllers\Web\CRM\SmsCampaignWebController;
use App\Http\Controllers\Web\CRM\UnifiedInboxWebController;
use App\Http\Controllers\Web\CRM\WebFormWebController;
use App\Http\Controllers\Web\CRM\WhatsAppBroadcastWebController;
use App\Http\Controllers\Web\CRM\WhatsAppWebController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Public routes (no auth — web enquiry forms)
// -----------------------------------------------------------------------
Route::middleware(['throttle:60,1'])->group(function (): void {
    // BRD: CRM-LC-001 — Public web enquiry form routes
    Route::get('/f/{slug}', [PublicFormController::class, 'show'])->name('public.form.show');
    Route::get('/f/{slug}/embed', [PublicFormController::class, 'embed'])->name('public.form.embed');
    Route::post('/f/{slug}', [PublicFormController::class, 'submit'])->name('public.form.submit');
    Route::get('/lp/{slug}', [PublicLandingPageController::class, 'show'])->name('public.landing-pages.show');
    Route::get('/chat/widget/{institution:uuid}', [PublicChatWidgetController::class, 'show'])->name('public.chat-widget.show');
    Route::post('/chat/widget/{institution:uuid}/submit', [PublicChatWidgetController::class, 'submit'])->name('public.chat-widget.submit');
    Route::get('/kiosk/{institution:uuid}', [PublicKioskController::class, 'show'])->name('public.kiosk.show');
    Route::post('/kiosk/{institution:uuid}/submit', [PublicKioskController::class, 'submit'])->name('public.kiosk.submit');

    // BRD: CRM-EC-016 — Public appointment booking (lead UUID as slug, rate-limited)
    Route::get('/book/{slug}', [PublicBookingController::class, 'show'])->name('public.booking.show');
    Route::post('/book/{slug}', [PublicBookingController::class, 'submit'])->name('public.booking.submit');
    Route::get('/book/{slug}/confirmation', [PublicBookingController::class, 'confirmation'])->name('public.booking.confirmation');
});

// -----------------------------------------------------------------------
// Guest routes
// -----------------------------------------------------------------------
Route::middleware('guest')->group(function (): void {
    Route::get('/', fn () => redirect()->route('login'));

    Route::get('/login', fn () => view('auth.login'))->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    })->name('login.post');
});

// -----------------------------------------------------------------------
// Authenticated routes
// -----------------------------------------------------------------------
Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    // BRD: CRM-LC-011 — Lead management web views
    Route::prefix('crm')->name('crm.')->group(function (): void {
        Route::get('/leads', [LeadWebController::class, 'index'])
            ->name('leads.index')
            ->middleware('can:crm.leads.view');
        // BRD: CRM-LC-011 — Web form POST; session auth, returns JSON for the modal
        Route::post('/leads', [LeadWebController::class, 'store'])
            ->name('leads.store')
            ->middleware('can:crm.leads.create');
        Route::get('/leads/{lead:uuid}', [LeadWebController::class, 'show'])
            ->name('leads.show')
            ->middleware('can:crm.leads.view');
        // BRD: CRM-LC-011 — Web PUT/DELETE; session auth, returns JSON for modals
        Route::put('/leads/{lead:uuid}', [LeadWebController::class, 'update'])
            ->name('leads.update')
            ->middleware('can:crm.leads.edit');
        Route::delete('/leads/{lead:uuid}', [LeadWebController::class, 'destroy'])
            ->name('leads.destroy')
            ->middleware('can:crm.leads.delete');

        // BRD: CRM-LC-001 — Web form management routes (auth)
        Route::get('/forms', [WebFormWebController::class, 'index'])
            ->name('forms.index')
            ->middleware('can:crm.forms.view');
        Route::get('/forms/create', [WebFormWebController::class, 'create'])
            ->name('forms.create')
            ->middleware('can:crm.forms.create');
        Route::post('/forms', [WebFormWebController::class, 'store'])
            ->name('forms.store')
            ->middleware('can:crm.forms.create');
        Route::get('/forms/{form:uuid}/edit', [WebFormWebController::class, 'edit'])
            ->name('forms.edit')
            ->middleware('can:crm.forms.edit');
        Route::put('/forms/{form:uuid}', [WebFormWebController::class, 'update'])
            ->name('forms.update')
            ->middleware('can:crm.forms.edit');
        // BRD: CRM-LC-001 + LC-009 — Embed code + QR download page
        Route::get('/forms/{form:uuid}/embed-code', [WebFormWebController::class, 'embedCode'])
            ->name('forms.embed-code')
            ->middleware('can:crm.forms.view');
        // BRD: CRM-LC-001 — Preview any form (draft or published) — auth only
        Route::get('/forms/{form:uuid}/preview', [WebFormWebController::class, 'preview'])
            ->name('forms.preview')
            ->middleware('can:crm.forms.view');

        // BRD: CRM-LC-005 — Landing page builder and publishing
        Route::prefix('marketing/landing-pages')->name('marketing.landing-pages.')->group(function (): void {
            Route::get('/', [LandingPageWebController::class, 'index'])
                ->name('index')
                ->middleware('can:crm.campaigns.manage');
            Route::get('/create', [LandingPageWebController::class, 'create'])
                ->name('create')
                ->middleware('can:crm.campaigns.manage');
            Route::post('/', [LandingPageWebController::class, 'store'])
                ->name('store')
                ->middleware('can:crm.campaigns.manage');
            Route::get('/{landingPage:uuid}/edit', [LandingPageWebController::class, 'edit'])
                ->name('edit')
                ->middleware('can:crm.campaigns.manage');
            Route::put('/{landingPage:uuid}', [LandingPageWebController::class, 'update'])
                ->name('update')
                ->middleware('can:crm.campaigns.manage');
            Route::delete('/{landingPage:uuid}', [LandingPageWebController::class, 'destroy'])
                ->name('destroy')
                ->middleware('can:crm.campaigns.manage');
        });

        // BRD: CRM-LC-006 — Live chat widget embed + captured session monitoring
        Route::get('/marketing/chat-widget', [ChatWidgetWebController::class, 'index'])
            ->name('marketing.chat-widget.index')
            ->middleware('can:crm.chat-widget.manage');
        Route::post('/marketing/chat-widget/{chatLead:uuid}/reply', [ChatWidgetWebController::class, 'reply'])
            ->name('marketing.chat-widget.reply')
            ->middleware('can:crm.chat-widget.manage');
        Route::post('/marketing/chat-widget/{chatLead:uuid}/ai-reply', [ChatWidgetWebController::class, 'generateAiReply'])
            ->name('marketing.chat-widget.ai-reply')
            ->middleware('can:crm.chat-widget.manage');
        Route::patch('/marketing/chat-widget/{chatLead:uuid}/handoff', [ChatWidgetWebController::class, 'updateHandoff'])
            ->name('marketing.chat-widget.handoff')
            ->middleware('can:crm.chat-widget.manage');

        // BRD: CRM-LC-013 — Walk-in kiosk setup and captured enquiry monitoring
        Route::get('/marketing/kiosk', [KioskWebController::class, 'index'])
            ->name('marketing.kiosk.index')
            ->middleware('can:crm.campaigns.manage');

        // BRD: CRM-LC-016 — Multi-touch attribution timeline and touchpoint capture
        Route::get('/marketing/attribution', [AttributionWebController::class, 'index'])
            ->name('marketing.attribution.index')
            ->middleware('can:crm.campaigns.manage');
        Route::post('/marketing/attribution/leads/{lead:uuid}/touchpoints', [AttributionWebController::class, 'store'])
            ->name('marketing.attribution.store')
            ->middleware('can:crm.campaigns.manage');

        // BRD: CRM-LC-017 — Campaign spend entry and cost-per-lead report
        Route::get('/marketing/cost-tracking', [CostTrackingWebController::class, 'index'])
            ->name('marketing.cost-tracking.index')
            ->middleware('can:crm.campaigns.manage');
        Route::post('/marketing/cost-tracking/spends', [CostTrackingWebController::class, 'store'])
            ->name('marketing.cost-tracking.store')
            ->middleware('can:crm.campaigns.manage');

        // BRD: CRM-MA-001 — Visual workflow builder CRUD routes for marketing automation
        Route::prefix('marketing/automation-workflows')->name('marketing.automation-workflows.')->group(function (): void {
            Route::get('/', [AutomationWorkflowWebController::class, 'index'])
                ->name('index')
                ->middleware('can:crm.campaigns.manage');
            Route::get('/create', [AutomationWorkflowWebController::class, 'create'])
                ->name('create')
                ->middleware('can:crm.campaigns.manage');
            Route::post('/', [AutomationWorkflowWebController::class, 'store'])
                ->name('store')
                ->middleware('can:crm.campaigns.manage');
            Route::get('/{automationWorkflow:uuid}/edit', [AutomationWorkflowWebController::class, 'edit'])
                ->name('edit')
                ->middleware('can:crm.campaigns.manage');
            Route::put('/{automationWorkflow:uuid}', [AutomationWorkflowWebController::class, 'update'])
                ->name('update')
                ->middleware('can:crm.campaigns.manage');
            Route::delete('/{automationWorkflow:uuid}', [AutomationWorkflowWebController::class, 'destroy'])
                ->name('destroy')
                ->middleware('can:crm.campaigns.manage');
        });

        // BRD: CRM-LC-012 — Bulk CSV/Excel import routes
        Route::get('/imports', [LeadImportWebController::class, 'index'])
            ->name('imports.index')
            ->middleware('can:crm.leads.import');
        Route::get('/imports/upload', [LeadImportWebController::class, 'upload'])
            ->name('imports.upload')
            ->middleware('can:crm.leads.import');
        Route::post('/imports', [LeadImportWebController::class, 'store'])
            ->name('imports.store')
            ->middleware('can:crm.leads.import');
        Route::get('/imports/{batch:uuid}/report', [LeadImportWebController::class, 'downloadReport'])
            ->name('imports.report')
            ->middleware('can:crm.leads.import');

        // BRD: CRM-SA-010 — Integration credential management (settings)
        Route::prefix('settings')->name('settings.')->group(function (): void {
            Route::get('/integrations', [IntegrationWebController::class, 'index'])
                ->name('integrations.index')
                ->middleware('can:crm.integrations.view');
            Route::get('/integrations/create', [IntegrationWebController::class, 'create'])
                ->name('integrations.create')
                ->middleware('can:crm.integrations.manage');
            Route::post('/integrations', [IntegrationWebController::class, 'store'])
                ->name('integrations.store')
                ->middleware('can:crm.integrations.manage');
            Route::get('/integrations/{integration:uuid}/edit', [IntegrationWebController::class, 'edit'])
                ->name('integrations.edit')
                ->middleware('can:crm.integrations.manage');
            Route::put('/integrations/{integration:uuid}', [IntegrationWebController::class, 'update'])
                ->name('integrations.update')
                ->middleware('can:crm.integrations.manage');
            Route::delete('/integrations/{integration:uuid}', [IntegrationWebController::class, 'destroy'])
                ->name('integrations.destroy')
                ->middleware('can:crm.integrations.manage');
        });

        // BRD: CRM-LQ-001, CRM-LQ-005, CRM-LQ-007, CRM-LQ-008 — Lead scoring configuration + reports
        Route::prefix('scoring')->name('scoring.')->group(function (): void {
            Route::get('/config', [LeadScoringWebController::class, 'config'])
                ->name('config');
            Route::post('/config', [LeadScoringWebController::class, 'updateConfig'])
                ->name('config.update');
            Route::get('/source-quality', [LeadScoringWebController::class, 'sourceQualityReport'])
                ->name('source-quality');
            // BRD: CRM-LQ-009 — Qualification questionnaire listing (web)
            Route::get('/questionnaires', [QuestionnaireWebController::class, 'index'])
                ->name('questionnaires.index')
                ->middleware('can:crm.questionnaires.manage');
            Route::get('/questionnaires/create', [QuestionnaireWebController::class, 'create'])
                ->name('questionnaires.create')
                ->middleware('can:crm.questionnaires.manage');
            Route::post('/questionnaires', [QuestionnaireWebController::class, 'store'])
                ->name('questionnaires.store')
                ->middleware('can:crm.questionnaires.manage');
            Route::get('/questionnaires/{questionnaire:uuid}/edit', [QuestionnaireWebController::class, 'edit'])
                ->name('questionnaires.edit')
                ->middleware('can:crm.questionnaires.manage');
            Route::put('/questionnaires/{questionnaire:uuid}', [QuestionnaireWebController::class, 'update'])
                ->name('questionnaires.update')
                ->middleware('can:crm.questionnaires.manage');
            Route::delete('/questionnaires/{questionnaire:uuid}', [QuestionnaireWebController::class, 'destroy'])
                ->name('questionnaires.destroy')
                ->middleware('can:crm.questionnaires.manage');
            Route::post('/questionnaires/{questionnaire:uuid}/responses/{lead:uuid}', [QuestionnaireWebController::class, 'storeResponse'])
                ->name('questionnaires.responses.store')
                ->middleware('can:crm.questionnaires.respond')
                ->withoutScopedBindings();
            Route::get('/priority-leads', [LeadScoringWebController::class, 'priorityLeads'])
                ->name('priority-leads')
                ->middleware('can:crm.leads.view');
            Route::post('/priority-leads/generate', [LeadScoringWebController::class, 'triggerPriorityLeadGeneration'])
                ->name('priority-leads.generate')
                ->middleware('can:crm.leads.edit');
            Route::get('/enrolment-forecasts', [LeadScoringWebController::class, 'enrolmentForecasts'])
                ->name('enrolment-forecasts')
                ->middleware('can:crm.leads.view');
            Route::post('/enrolment-forecasts/generate', [LeadScoringWebController::class, 'triggerEnrolmentForecastGeneration'])
                ->name('enrolment-forecasts.generate')
                ->middleware('can:crm.leads.edit');
            Route::get('/anomaly-alerts', [LeadScoringWebController::class, 'anomalyAlerts'])
                ->name('anomaly-alerts')
                ->middleware('can:crm.leads.view');
            Route::post('/anomaly-alerts/detect', [LeadScoringWebController::class, 'triggerAnomalyDetection'])
                ->name('anomaly-alerts.detect')
                ->middleware('can:crm.leads.edit');
            Route::get('/nba-journeys', [LeadScoringWebController::class, 'nbaJourneys'])
                ->name('nba-journeys')
                ->middleware('can:crm.leads.view');
            Route::post('/nba-journeys/generate', [LeadScoringWebController::class, 'triggerNbaJourneyGeneration'])
                ->name('nba-journeys.generate')
                ->middleware('can:crm.leads.edit');
            Route::post('/ai-suggestions/decision', [LeadScoringWebController::class, 'storeAiSuggestionDecision'])
                ->name('ai-suggestions.decision')
                ->middleware('can:crm.leads.edit');
            Route::get('/ai-usage-logs', [LeadScoringWebController::class, 'aiUsageLogs'])
                ->name('ai-usage-logs')
                ->middleware('can:crm.leads.view');
        });

        // BRD: CRM-LQ-007 — Manual score override (posted from lead show page)
        Route::post('/leads/{lead:uuid}/score-override', [LeadScoringWebController::class, 'override'])
            ->name('leads.score-override')
            ->middleware('can:crm.leads.edit');

        // BRD: CRM-LQ-003 — AI score fetch/trigger routes for lead scoring UI
        Route::get('/leads/{lead:uuid}/ai-score', [LeadScoringWebController::class, 'aiScore'])
            ->name('leads.ai-score')
            ->middleware('can:crm.leads.view');
        Route::post('/leads/{lead:uuid}/ai-score/recalculate', [LeadScoringWebController::class, 'triggerAiRecalculation'])
            ->name('leads.ai-score.recalculate')
            ->middleware('can:crm.leads.edit');
        Route::get('/leads/{lead:uuid}/churn-risk', [LeadScoringWebController::class, 'churnRisk'])
            ->name('leads.churn-risk')
            ->middleware('can:crm.leads.view');
        Route::post('/leads/{lead:uuid}/churn-risk/recalculate', [LeadScoringWebController::class, 'triggerChurnRecalculation'])
            ->name('leads.churn-risk.recalculate')
            ->middleware('can:crm.leads.edit');
        Route::get('/leads/{lead:uuid}/next-best-action', [LeadScoringWebController::class, 'nextBestAction'])
            ->name('leads.next-best-action')
            ->middleware('can:crm.leads.view');
        Route::post('/leads/{lead:uuid}/next-best-action/recalculate', [LeadScoringWebController::class, 'triggerNbaRecalculation'])
            ->name('leads.next-best-action.recalculate')
            ->middleware('can:crm.leads.edit');
        Route::get('/leads/{lead:uuid}/ai-drafts', [LeadScoringWebController::class, 'aiMessageDraft'])
            ->name('leads.ai-drafts')
            ->middleware('can:crm.leads.view');
        Route::post('/leads/{lead:uuid}/ai-drafts/generate', [LeadScoringWebController::class, 'triggerAiMessageDraft'])
            ->name('leads.ai-drafts.generate')
            ->middleware('can:crm.communication.send');
        Route::get('/leads/{lead:uuid}/sentiment', [LeadScoringWebController::class, 'sentiment'])
            ->name('leads.sentiment')
            ->middleware('can:crm.leads.view');
        Route::post('/leads/{lead:uuid}/sentiment/recalculate', [LeadScoringWebController::class, 'triggerSentimentRecalculation'])
            ->name('leads.sentiment.recalculate')
            ->middleware('can:crm.leads.edit');

        // BRD: CRM-EC-016 — Public booking confirmation (auth not needed — already routed)
        // BRD: CRM-EC-015 — Internal session booking per lead
        Route::get('/leads/{lead:uuid}/sessions', [SessionWebController::class, 'index'])
            ->name('leads.sessions.index')
            ->middleware('can:crm.sessions.view');
        Route::get('/leads/{lead:uuid}/sessions/create', [SessionWebController::class, 'create'])
            ->name('leads.sessions.create')
            ->middleware('can:crm.sessions.create');
        Route::post('/leads/{lead:uuid}/sessions', [SessionWebController::class, 'store'])
            ->name('leads.sessions.store')
            ->middleware('can:crm.sessions.create');

        // BRD: CRM-EC-015 — Session outcome update and cancellation (JSON; called from Alpine modal)
        Route::put('/sessions/{session}', [SessionWebController::class, 'update'])
            ->name('sessions.update')
            ->middleware('can:crm.sessions.edit');
        Route::delete('/sessions/{session}', [SessionWebController::class, 'destroy'])
            ->name('sessions.destroy')
            ->middleware('can:crm.sessions.cancel');

        // BRD: CRM-EC-007 — Manual counsellor assignment (AJAX from lead show page)
        Route::post('/leads/{lead:uuid}/assign', [CounsellingWebController::class, 'assignCounsellor'])
            ->name('leads.assign');

        // BRD: CRM-EC-006 — Assignment config + workload dashboard
        Route::prefix('settings')->name('settings.')->group(function (): void {
            Route::get('/assignment-config', [CounsellingWebController::class, 'assignmentConfig'])
                ->name('assignment-config')
                ->middleware('can:crm.settings.manage');
            Route::post('/assignment-config', [CounsellingWebController::class, 'updateAssignmentConfig'])
                ->name('assignment-config.update')
                ->middleware('can:crm.settings.manage');
        });

        // BRD: CRM-EC-008 — Counsellor workload view
        Route::get('/counsellors/workload', [CounsellingWebController::class, 'workloadDashboard'])
            ->name('counsellors.workload')
            ->middleware('can:crm.leads.view');

        // -----------------------------------------------------------------------
        // Group F — Communication Engine (F1: Email, F2: SMS, F3: WhatsApp,
        //           F4: Voice/IVR, F5: Unified Inbox)
        // BRD: CRM-CC-001 to CRM-CC-025
        // -----------------------------------------------------------------------

        // F1 + F2: Communication templates
        Route::prefix('communication')->name('communication.')->group(function (): void {
            Route::resource('templates', CommunicationTemplateWebController::class)
                ->parameters(['templates' => 'template:uuid'])
                ->middleware('can:crm.communication.send');

            // F1: Email campaigns
            Route::prefix('email')->name('email.')->group(function (): void {
                Route::resource('campaigns', EmailCampaignWebController::class)
                    ->parameters(['campaigns' => 'emailCampaign:uuid'])
                    ->middleware('can:crm.communication.send');
                Route::post('campaigns/{emailCampaign:uuid}/launch', [EmailCampaignWebController::class, 'launch'])
                    ->name('campaigns.launch')
                    ->middleware('can:crm.communication.send');
            });

            // F2: SMS campaigns
            Route::prefix('sms')->name('sms.')->group(function (): void {
                Route::resource('campaigns', SmsCampaignWebController::class)
                    ->parameters(['campaigns' => 'smsCampaign:uuid'])
                    ->middleware('can:crm.communication.send');
                Route::post('campaigns/{smsCampaign:uuid}/launch', [SmsCampaignWebController::class, 'launch'])
                    ->name('campaigns.launch')
                    ->middleware('can:crm.communication.send');

                // F2: DLT template management
                Route::prefix('dlt')->name('dlt.')->group(function (): void {
                    Route::resource('templates', DltTemplateWebController::class)
                        ->parameters(['templates' => 'dltTemplate:uuid'])
                        ->middleware('can:crm.communication.send');
                    Route::post('templates/{dltTemplate:uuid}/submit', [DltTemplateWebController::class, 'submitForApproval'])
                        ->name('submit')
                        ->middleware('can:crm.communication.send');
                });
            });

            // F3: WhatsApp inbox + conversations + broadcasts
            Route::prefix('whatsapp')->name('whatsapp.')->group(function (): void {
                Route::get('/', [WhatsAppWebController::class, 'index'])
                    ->name('index')
                    ->middleware('can:crm.communication.send');

                // BRD: CRM-CC-015 — WhatsApp broadcast campaigns
                Route::prefix('broadcasts')->name('broadcasts.')->middleware('can:crm.campaigns.send')->group(function (): void {
                    Route::get('/', [WhatsAppBroadcastWebController::class, 'index'])->name('index');
                    Route::get('/create', [WhatsAppBroadcastWebController::class, 'create'])->name('create');
                    Route::post('/', [WhatsAppBroadcastWebController::class, 'store'])->name('store');
                    Route::get('/{broadcast:uuid}', [WhatsAppBroadcastWebController::class, 'show'])->name('show');
                    Route::post('/{broadcast:uuid}/launch', [WhatsAppBroadcastWebController::class, 'launch'])->name('launch');
                });

                Route::get('{conversation:uuid}', [WhatsAppWebController::class, 'show'])
                    ->name('conversation')
                    ->middleware('can:crm.communication.send');
                Route::post('{conversation:uuid}/send', [WhatsAppWebController::class, 'send'])
                    ->name('send')
                    ->middleware('can:crm.communication.send');
                Route::post('{conversation:uuid}/assign', [WhatsAppWebController::class, 'assign'])
                    ->name('assign')
                    ->middleware('can:crm.communication.send');
            });

            // F4: Call log (read) + click-to-call
            Route::prefix('voice')->name('voice.')->group(function (): void {
                Route::get('/', [CallLogWebController::class, 'index'])
                    ->name('index')
                    ->middleware('can:crm.communication.send');
                // BRD: CRM-TC-003 — Configurable call dispositions
                Route::get('dispositions', [CallDispositionWebController::class, 'index'])
                    ->name('dispositions.index')
                    ->middleware('can:crm.settings.manage');
                Route::post('dispositions', [CallDispositionWebController::class, 'store'])
                    ->name('dispositions.store')
                    ->middleware('can:crm.settings.manage');
                Route::put('dispositions/{callDispositionConfig:uuid}', [CallDispositionWebController::class, 'update'])
                    ->name('dispositions.update')
                    ->middleware('can:crm.settings.manage');
                // BRD: CRM-TC-005 — Supervisor monitoring (listen/whisper/barge-in)
                Route::get('monitor', [CallMonitorWebController::class, 'index'])
                    ->name('monitor.index')
                    ->middleware('can:crm.communication.send');
                Route::post('monitor', [CallMonitorWebController::class, 'store'])
                    ->name('monitor.store')
                    ->middleware('can:crm.communication.send');
                Route::post('monitor/{callMonitorLog:uuid}/stop', [CallMonitorWebController::class, 'stop'])
                    ->name('monitor.stop')
                    ->middleware('can:crm.communication.send');
                // BRD: CRM-TC-002 — Call scripts with branching
                Route::get('scripts', [CallScriptWebController::class, 'index'])
                    ->name('scripts.index')
                    ->middleware('can:crm.communication.send');
                Route::post('scripts', [CallScriptWebController::class, 'store'])
                    ->name('scripts.store')
                    ->middleware('can:crm.communication.send');
                Route::get('scripts/{callScript:uuid}', [CallScriptWebController::class, 'show'])
                    ->name('scripts.show')
                    ->middleware('can:crm.communication.send');
                Route::put('scripts/{callScript:uuid}', [CallScriptWebController::class, 'update'])
                    ->name('scripts.update')
                    ->middleware('can:crm.communication.send');
                Route::delete('scripts/{callScript:uuid}', [CallScriptWebController::class, 'destroy'])
                    ->name('scripts.destroy')
                    ->middleware('can:crm.communication.send');
                Route::post('scripts/{callScript:uuid}/resolve', [CallScriptWebController::class, 'resolve'])
                    ->name('scripts.resolve')
                    ->middleware('can:crm.communication.send');
                // BRD: CRM-TC-006 — Telecalling campaign definition, assignment, launch, and progress
                Route::get('campaigns', [TelecallingCampaignWebController::class, 'index'])
                    ->name('campaigns.index')
                    ->middleware('can:crm.campaigns.manage');
                Route::get('campaigns/{telecallingCampaign:uuid}/edit', [TelecallingCampaignWebController::class, 'edit'])
                    ->name('campaigns.edit')
                    ->middleware('can:crm.campaigns.manage');
                Route::post('campaigns', [TelecallingCampaignWebController::class, 'store'])
                    ->name('campaigns.store')
                    ->middleware('can:crm.campaigns.manage');
                Route::put('campaigns/{telecallingCampaign:uuid}', [TelecallingCampaignWebController::class, 'update'])
                    ->name('campaigns.update')
                    ->middleware('can:crm.campaigns.manage');
                Route::post('campaigns/{telecallingCampaign:uuid}/launch', [TelecallingCampaignWebController::class, 'launch'])
                    ->name('campaigns.launch')
                    ->middleware('can:crm.campaigns.manage');
                // BRD: CRM-TC-007 — Call centre performance dashboard
                Route::get('performance', [CallCentrePerformanceWebController::class, 'index'])
                    ->name('performance')
                    ->middleware('can:crm.voice.performance');
                Route::get('dialler', [DiallerWebController::class, 'index'])
                    ->name('dialler.index')
                    ->middleware('can:crm.communication.send');
                Route::post('dialler/start', [DiallerWebController::class, 'store'])
                    ->name('dialler.start')
                    ->middleware('can:crm.communication.send');
                Route::post('dialler/{diallerSession:uuid}/stop', [DiallerWebController::class, 'stop'])
                    ->name('dialler.stop')
                    ->middleware('can:crm.communication.send');
                Route::post('dialler/{diallerSession:uuid}/dispatch-next', [DiallerWebController::class, 'dispatchNext'])
                    ->name('dialler.next')
                    ->middleware('can:crm.communication.send');
                Route::post('calls/{callLog:uuid}/disposition', [CallLogWebController::class, 'updateDisposition'])
                    ->name('calls.disposition')
                    ->middleware('can:crm.communication.send');
                Route::get('calls/{callLog:uuid}/recording', [CallLogWebController::class, 'playRecording'])
                    ->name('calls.recording')
                    ->middleware('can:crm.communication.send');
                Route::post('leads/{lead:uuid}/call', [CallLogWebController::class, 'initiateCall'])
                    ->name('leads.call')
                    ->middleware('can:crm.communication.send');
                // BRD: CRM-TC-009 — Do-Not-Call (DNC) list management
                Route::get('dnc', [DncWebController::class, 'index'])
                    ->name('dnc.index')
                    ->middleware('can:crm.dnc.manage');
                Route::post('dnc/{lead:uuid}', [DncWebController::class, 'store'])
                    ->name('dnc.store')
                    ->middleware('can:crm.dnc.manage');
                Route::delete('dnc/{lead:uuid}', [DncWebController::class, 'destroy'])
                    ->name('dnc.destroy')
                    ->middleware('can:crm.dnc.manage');
            });
        });

        // -----------------------------------------------------------------------
        // Group G — Duplicate Merge + ERP Lead Match
        // BRD: CRM-LC-019, CRM-LC-020
        // -----------------------------------------------------------------------

        // BRD: CRM-LC-019 — Manual lead merge (irreversible, requires crm.leads.merge)
        Route::post('/leads/{lead:uuid}/merge', LeadMergeWebController::class)
            ->name('leads.merge')
            ->middleware('can:crm.leads.merge');
        Route::get('/leads/{lead:uuid}/merge-status', [LeadMergeWebController::class, 'status'])
            ->name('leads.merge-status')
            ->middleware('can:crm.leads.view');

        // BRD: CRM-LC-020 — Manual trigger for ERP Student Master check
        Route::post('/leads/{lead:uuid}/check-erp', ErpMatchWebController::class)
            ->name('leads.check-erp')
            ->middleware('can:crm.leads.edit');

        // F5: Unified Inbox
        Route::prefix('inbox')->name('inbox.')->group(function (): void {
            Route::get('/', [UnifiedInboxWebController::class, 'index'])
                ->name('index')
                ->middleware('can:crm.communication.send');
            Route::post('conversations/{conversation:uuid}/read', [UnifiedInboxWebController::class, 'markRead'])
                ->name('mark-read')
                ->middleware('can:crm.communication.send');
            Route::post('conversations/{conversation:uuid}/assign', [UnifiedInboxWebController::class, 'assign'])
                ->name('assign')
                ->middleware('can:crm.communication.send');
            Route::get('unread-counts', [UnifiedInboxWebController::class, 'unreadCounts'])
                ->name('unread-counts')
                ->middleware('can:crm.communication.send');
        });

        // BRD: CRM-EC-010 — Counsellor performance gamification dashboard
        Route::get('/gamification', [\App\Http\Controllers\CRM\Web\GamificationController::class, 'index'])
            ->name('gamification.index')
            ->middleware('can:crm.leads.view');

        // F1 + F4 + IVR: Settings
        Route::prefix('settings')->name('settings.')->group(function (): void {
            // Sender domain management (F1)
            Route::resource('sender-domains', SenderDomainWebController::class)
                ->parameters(['sender-domains' => 'senderDomain:uuid'])
                ->middleware('can:crm.settings.manage');
            Route::post('sender-domains/{senderDomain:uuid}/check-dns', [SenderDomainWebController::class, 'checkDns'])
                ->name('sender-domains.check-dns')
                ->middleware('can:crm.settings.manage');

            // IVR configuration (F4)
            Route::resource('ivr', IvrConfigWebController::class)
                ->parameters(['ivr' => 'ivrConfig:uuid'])
                ->middleware('can:crm.settings.manage');
            Route::post('ivr/{ivrConfig:uuid}/toggle', [IvrConfigWebController::class, 'toggleActive'])
                ->name('ivr.toggle')
                ->middleware('can:crm.settings.manage');

            // BRD: CRM-EC-005 — Custom field definitions per entity (leads, applications)
            Route::get('custom-fields', [CustomFieldWebController::class, 'index'])
                ->name('custom-fields.index')
                ->middleware('can:crm.settings.custom-fields.view');
            Route::post('custom-fields', [CustomFieldWebController::class, 'store'])
                ->name('custom-fields.store')
                ->middleware('can:crm.settings.custom-fields.manage');
            Route::put('custom-fields/{customField:uuid}', [CustomFieldWebController::class, 'update'])
                ->name('custom-fields.update')
                ->middleware('can:crm.settings.custom-fields.manage');
            Route::delete('custom-fields/{customField:uuid}', [CustomFieldWebController::class, 'destroy'])
                ->name('custom-fields.destroy')
                ->middleware('can:crm.settings.custom-fields.manage');

            // BRD: CRM-SA-007 — Workflow template library
            Route::get('workflow-templates', [WorkflowTemplateWebController::class, 'index'])
                ->name('workflow-templates.index')
                ->middleware('can:crm.settings.custom-fields.view');
            Route::get('workflow-templates/create', [WorkflowTemplateWebController::class, 'create'])
                ->name('workflow-templates.create')
                ->middleware('can:crm.settings.custom-fields.manage');
            Route::post('workflow-templates', [WorkflowTemplateWebController::class, 'store'])
                ->name('workflow-templates.store')
                ->middleware('can:crm.settings.custom-fields.manage');
            Route::get('workflow-templates/{workflowTemplate:uuid}/edit', [WorkflowTemplateWebController::class, 'edit'])
                ->name('workflow-templates.edit')
                ->middleware('can:crm.settings.custom-fields.manage');
            Route::put('workflow-templates/{workflowTemplate:uuid}', [WorkflowTemplateWebController::class, 'update'])
                ->name('workflow-templates.update')
                ->middleware('can:crm.settings.custom-fields.manage');
            Route::delete('workflow-templates/{workflowTemplate:uuid}', [WorkflowTemplateWebController::class, 'destroy'])
                ->name('workflow-templates.destroy')
                ->middleware('can:crm.settings.custom-fields.manage');
            Route::post('workflow-templates/{workflowTemplate:uuid}/import', [WorkflowTemplateWebController::class, 'import'])
                ->name('workflow-templates.import')
                ->middleware('can:crm.settings.custom-fields.manage');
        });

        // BRD: CRM-AR-018 — Custom report builder
        Route::prefix('reports/custom')->name('reports.custom.')->group(function (): void {
            Route::get('/', [CustomReportWebController::class, 'index'])
                ->name('index')
                ->middleware('can:crm.reports.view');
            Route::get('/create', [CustomReportWebController::class, 'create'])
                ->name('create')
                ->middleware('can:crm.reports.manage');
            Route::post('/', [CustomReportWebController::class, 'store'])
                ->name('store')
                ->middleware('can:crm.reports.manage');
            Route::get('/{customReport:uuid}', [CustomReportWebController::class, 'show'])
                ->name('show')
                ->middleware('can:crm.reports.view');
            Route::get('/{customReport:uuid}/edit', [CustomReportWebController::class, 'edit'])
                ->name('edit')
                ->middleware('can:crm.reports.manage');
            Route::put('/{customReport:uuid}', [CustomReportWebController::class, 'update'])
                ->name('update')
                ->middleware('can:crm.reports.manage');
            Route::delete('/{customReport:uuid}', [CustomReportWebController::class, 'destroy'])
                ->name('destroy')
                ->middleware('can:crm.reports.manage');
            Route::post('/{customReport:uuid}/run', [CustomReportWebController::class, 'run'])
                ->name('run')
                ->middleware('can:crm.reports.view');
        });

        // BRD: CRM-AR-020 — Scheduled report delivery
        Route::prefix('reports/scheduler')->name('reports.scheduler.')->group(function (): void {
            Route::get('/', [ReportSchedulerWebController::class, 'index'])
                ->name('index')
                ->middleware('can:crm.reports.view');
            Route::get('/create', [ReportSchedulerWebController::class, 'create'])
                ->name('create')
                ->middleware('can:crm.reports.manage');
            Route::post('/', [ReportSchedulerWebController::class, 'store'])
                ->name('store')
                ->middleware('can:crm.reports.manage');
            Route::get('/{reportSchedule:uuid}/edit', [ReportSchedulerWebController::class, 'edit'])
                ->name('edit')
                ->middleware('can:crm.reports.manage');
            Route::put('/{reportSchedule:uuid}', [ReportSchedulerWebController::class, 'update'])
                ->name('update')
                ->middleware('can:crm.reports.manage');
            Route::delete('/{reportSchedule:uuid}', [ReportSchedulerWebController::class, 'destroy'])
                ->name('destroy')
                ->middleware('can:crm.reports.manage');
            Route::post('/{reportSchedule:uuid}/dispatch', [ReportSchedulerWebController::class, 'dispatch'])
                ->name('dispatch')
                ->middleware('can:crm.reports.manage');
        });

        // BRD: CRM-SA-011 — System health monitoring dashboard
        Route::prefix('admin/system-health')->name('admin.system-health.')->group(function (): void {
            Route::get('/', [SystemHealthWebController::class, 'index'])
                ->name('index')
                ->middleware('can:crm.admin.system-health.view');
            Route::get('/poll', [SystemHealthWebController::class, 'poll'])
                ->name('poll')
                ->middleware('can:crm.admin.system-health.view');
            Route::get('/history/{component}', [SystemHealthWebController::class, 'history'])
                ->name('history')
                ->middleware('can:crm.admin.system-health.view');
        });
    });

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
