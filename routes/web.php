<?php

declare(strict_types=1);

use App\Http\Controllers\Public\PublicFormController;
use App\Http\Controllers\Web\CRM\IntegrationWebController;
use App\Http\Controllers\Web\CRM\LeadImportWebController;
use App\Http\Controllers\Web\CRM\LeadScoringWebController;
use App\Http\Controllers\Web\CRM\LeadWebController;
use App\Http\Controllers\Web\CRM\WebFormWebController;
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
});

// -----------------------------------------------------------------------
// Guest routes
// -----------------------------------------------------------------------
Route::middleware('guest')->group(function (): void {
    Route::get('/', fn () => redirect()->route('login'));

    Route::get('/login', fn () => view('auth.login'))->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
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
    });

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
