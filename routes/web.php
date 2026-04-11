<?php

declare(strict_types=1);

use App\Http\Controllers\Public\PublicFormController;
use App\Http\Controllers\Web\CRM\CallLogWebController;
use App\Http\Controllers\Web\CRM\ErpMatchWebController;
use App\Http\Controllers\Web\CRM\LeadMergeWebController;
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
        });

        // BRD: CRM-LQ-007 — Manual score override (posted from lead show page)
        Route::post('/leads/{lead:uuid}/score-override', [LeadScoringWebController::class, 'override'])
            ->name('leads.score-override')
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
                Route::post('calls/{callLog:uuid}/disposition', [CallLogWebController::class, 'updateDisposition'])
                    ->name('calls.disposition')
                    ->middleware('can:crm.communication.send');
                Route::post('leads/{lead:uuid}/call', [CallLogWebController::class, 'initiateCall'])
                    ->name('leads.call')
                    ->middleware('can:crm.communication.send');
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
        });
    });

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
